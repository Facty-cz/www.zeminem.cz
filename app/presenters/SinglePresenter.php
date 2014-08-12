<?php

namespace App;

use Nette;
use Nette\Http\Url;

class SinglePresenter extends BasePresenter {

	/** @var Tags @inject */
	public $tags;

	public function renderObsah() {
		$this->template->articles = $this->posts->findBy(array('publish_date <=' => new \DateTime()), array('title' => 'ASC'));
	}

	public function renderArticle($slug) {
		$webalized = Nette\Utils\Strings::webalize($slug);
		if (empty($webalized)) {
			$this->redirect('Homepage:default');
		}
		if ($slug !== $webalized) {
			$this->redirect('Single:article', $webalized);
		}
		$post = $this->posts->findOneBy(['slug' => $webalized, 'publish_date <=' => new \DateTime()]); // zobrazeni článku podle slugu
		if (!$post) { // pokud článek neexistuje (FALSE), pak forward - about, reference, atd...
			$this->forward($webalized);
		} else { // zobrazení klasických článků
			$texy = $this->prepareTexy();
			$texy->addHandler('phrase', function ($invocation, $phrase, $content, $modifier, $link) {
				$el = $invocation->proceed();
				if ($el instanceof \TexyHtml && $el->getName() === 'a') {
					//FIXME: nefunguje na ostrém serveru (?)
					$url = new Url($el->attrs['href']);
					$httpRequest = $this->presenter->getHttpRequest();
					$uri = $httpRequest->getUrl();
					if ($url->authority != $uri->host) {
						$el->attrs['target'] = '_blank';
					}
				}
				return $el;
			});

			$this->template->post = $post;
			$body = $texy->process($post->body);
			$this->template->body = $body;
			$this->template->wordCount = str_word_count(strip_tags($body));

			$ids = $next = array();
			if (isset($post->tags[0])) {
				$next = $this->posts->findBy(array('id !=' => $post->getId(), 'tags.id' => $post->tags), array('date' => 'DESC'), 3);
				foreach ($next as $n) {
					array_push($ids, $n->id);
				}
			}
			if (count($next) < 3) {
				$limit = 3 - count($next);
				if ($ids) {
					$next = array_merge((array)$next, (array)$this->posts->findBy(array('id !=' => $post->getId(), 'id != ' => $ids), array('date' => 'DESC'), $limit));
				} else {
					$next = array_merge((array)$next, (array)$this->posts->findBy(array('id !=' => $post->getId()), array('date' => 'DESC'), $limit));
				}
			}
			$this->template->next = $next;
		}
	}

}