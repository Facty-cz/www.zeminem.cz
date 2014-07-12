<?php

namespace App;

use App;
use Cntrl;
use Entity;
use Kdyby;
use Nette;

class AdminPresenter extends BasePresenter {

	public $onBeforeRestrictedFunctionality = [];

	/** @var Pictures @inject */
	public $pictures;
	/** @var Tags @inject */
	public $tags;
	/** @var Users @inject */
	public $users;

	/** @var \PostFormFactory @inject */
	public $postFormFactory;
	/** @var \UserEditFormFactory @inject */
	public $userEditFormFactory;

	private $id;

	public function startup() {
		parent::startup();
		if (!$this->user->isLoggedIn()) {
			if ($this->user->logoutReason === Nette\Security\IUserStorage::INACTIVITY) {
				$this->flashMessage('Byli jste odhlášeni z důvodu nečinnosti. Přihlaste se prosím znovu.', 'danger');
			}
			$this->redirect('Sign:in', array('backlink' => $this->storeRequest()));
		} elseif (!$this->user->isAllowed($this->name, Authorizator::VIEW)) {
			$this->flashMessage('Přístup byl odepřen. Nemáte oprávnění k zobrazení této stránky.', 'danger');
			$this->redirect('Sign:in', array('backlink' => $this->storeRequest()));
		}
	}

	public function beforeRender() {
		parent::beforeRender();

		$stack = \Stack::getStack();
		$this->template->menuStack = $stack->getMenu();

		$this->template->picturecount = $this->pictures->countBy();
		$this->template->tagcount = $this->tags->countBy();
		$this->template->usercount = $this->users->countBy();
		if (!$this->user->isAllowed('Admin', Authorizator::EDIT)) {
			$this->flashMessage('Nacházíte se v **demo** ukázce administrace. Máte právo prohlížet, nikoliv však editovat...', 'info');
		}
	}

	public function actionDefault($id = NULL) {
		$this->id = $id;
	}

	public function renderDefault($id = NULL) {
		$this->template->tags = $this->tags->findBy(array());
		$this->template->pictures = $this->pictures->findBy(array(), ['created' => 'DESC']);
		if ($id !== NULL) {
			$this->template->editace = TRUE;
		}
		$this->id = $id;
	}

	public function renderPictures() {
		$this->template->pictures = $this->pictures->findBy(array(), ['created' => 'DESC']);
	}

	public function renderPrehled() {
		$this->template->posts = $this->posts->findBy(array(), array('date' => 'DESC'));
	}

	public function renderTags() {
		$this->template->tags = $this->tags->findBy(array());
	}

	public function renderUsers() {
		$this->template->users = $this->users->findBy(array());
	}

	public function actionUserEdit($id = NULL) {
		$this->id = $id;
	}

	public function renderUserEdit($id = NULL) {
		$this->template->account = $this->users->findOneBy(['id' => $id]);
	}

	protected function createComponentUserEditForm() {
		$control = $this->userEditFormFactory->create($this->id);
		$control->onSave[] = function () {
			$this->redirect('users');
		};
		return $control;
	}

	protected function createComponentPostForm() {
		$control = $this->postFormFactory->create($this->id);
		$control->onSave[] = function () {
			$this->redirect('default');
		};
		return $control;
	}

	protected function createComponentColor() {
		$form = new Nette\Application\UI\Form;
		$form->addProtection();
		foreach ($this->tags->findBy(array()) as $tag) {
			$form->addText('color' . $tag->id)
				->setType('color')
				->setValue('#' . $tag->color);
			$form->addSubmit('update' . $tag->id, 'Změnit barvu')
				->onClick[] = function ($this) use ($tag) {
				$this->colorSucceeded($this, $tag->id);
			};
		}
		return $form;
	}

	/**
	 * @param $button
	 * @param $id
	 */
	public function colorSucceeded($button, $id) {
		$this->onBeforeRestrictedFunctionality($this);
		$vals = $button->getForm()->getValues();
		$newColor = preg_replace('<#>', '', $vals['color' . $id]);
		if (ctype_xdigit($newColor) && (strlen($newColor) == 6 || strlen($newColor) == 3)) {
			try {
				$tag = $this->tags->findOneBy(array('id' => $id));
				$tag->color = $newColor;
				$this->tags->save($tag);
				$this->flashMessage('Tag byl úspěšně aktualizován.', 'success');
			} catch (\Exception $exc) {
				$this->flashMessage($exc->getMessage(), 'danger');
			}
		} else {
			$this->flashMessage("Barva #$newColor není platnou hexadecimální hodnotou.", 'danger');
		}
		$this->redirect('this');
	}

	public function handleUpdate($title, $content, $tags, $slug) {
		$texy = $this->prepareTexy();
		$texy->imageModule->root = '../../uploads/';

		//podle slugu to není dobrý nápad (budou se množit) - musí se to udělat nějak chytřeji
		/*$article = $this->posts->findOneBy(['slug' => $slug]);
		if (!$article) {
			$article = new Entity\Post;
			$article->date = new \DateTime();
		}
		$article->title = $title;
		$article->slug = $slug;
		$article->body = $content;
		$this->posts->save($article);*/

		$this->template->preview = Nette\Utils\Html::el()->setHtml($texy->process($content));
		$this->template->title = $title;
		$this->template->tagsPrev = array_unique(explode(', ', $tags));
		if ($this->isAjax()) {
			$this->redrawControl('preview');
		} else {
			$this->redirect('this');
		}
	}

	public function handleDelete($id) {
		$this->onBeforeRestrictedFunctionality($this);
		try {
			$this->posts->delete($this->posts->findOneBy(array('id' => $id)));
			$this->flashMessage('Článek byl úspěšně smazán.', 'success');
		} catch (\Exception $exc) {
			$this->flashMessage($exc->getMessage(), 'danger');
		}
		$this->redirect('this');
	}

	public function handleDeleteTag($tag_id) {
		$this->onBeforeRestrictedFunctionality($this);
		try {
			$this->tags->delete($this->tags->findOneBy(array('id' => $tag_id)));
			$this->flashMessage('Tag byl úspěšně smazán.', 'success');
		} catch (\Exception $exc) {
			$this->flashMessage($exc->getMessage(), 'danger');
		}
		$this->redirect('this');
	}

	public function handleDeleteUser($user_id) {
		$this->onBeforeRestrictedFunctionality($this);
		try {
			$this->users->delete($this->users->findOneBy(['id' => $user_id]));
			$this->flashMessage('Uživatel byl úspěšně smazán.', 'success');
		} catch (\Exception $exc) {
			$this->flashMessage($exc->getMessage(), 'danger');
		}
		$this->redirect('this');
	}

	public function handleRegenerate($tag_id) {
		$this->onBeforeRestrictedFunctionality($this);
		try {
			$tag = $this->tags->findOneBy(array('id' => $tag_id));
			$tag->color = substr(md5(rand()), 0, 6); //Short and sweet
			$this->tags->save($tag);
			$this->flashMessage('Tag byl úspěšně regenerován.', 'success');
		} catch (\Exception $exc) {
			$this->flashMessage($exc->getMessage(), 'danger');
		}
		$this->redirect('this');
	}

	public function handleUploadReciever() {
		//ob_start();
		$uploader = new \UploadHandler();
		$uploader->allowedExtensions = array("jpeg", "jpg", "png", "gif");
		$uploader->chunksFolder = __DIR__ . '/../../www/chunks';
		$name = Nette\Utils\Strings::webalize($uploader->getName(), '.');
		//TODO: picture optimalization (?)
		$result = $uploader->handleUpload(__DIR__ . '/../../www/uploads', $name);
		try {
			$picture = $this->pictures->findOneBy(['uuid' => $uploader->getUuid()]);
			if (!$picture) { //FIXME: toto není optimální (zejména kvůli rychlosti)
				$picture = new Entity\Picture();
			}
			$picture->uuid = $uploader->getUuid();
			$picture->name = $name;
			$picture->created = new \DateTime('now');
			$this->pictures->save($picture);
		} catch (\Exception $exc) {
			$uploader->handleDelete(__DIR__ . '/../../www/uploads');
			$this->sendResponse(new Nette\Application\Responses\JsonResponse(array(
				'error' => $exc->getMessage(),
			)));
		}
		//TODO: napřed předat do šablony nová data
		$this->redrawControl('pictures');
		$this->sendResponse(new Nette\Application\Responses\JsonResponse($result));
	}

	public function handleDeletePicture($id) {
		$this->onBeforeRestrictedFunctionality($this);
		$picture = $this->pictures->findOneBy(['id' => $id]);
		@unlink(__DIR__ . '/../../www/uploads/' . $picture->uuid . DIRECTORY_SEPARATOR . $picture->name);
		@rmdir(__DIR__ . '/../../www/uploads/' . $picture->uuid);
		$this->pictures->delete($picture);
		$this->flashMessage('Obrázek byl úspěšně smazán.', 'success');
		$this->redirect('this');
	}

}
