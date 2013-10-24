<?php

namespace App;

class SearchPresenter extends BasePresenter {

	/** @var \Model\Posts @inject */
	public $posts;

	public function renderDefault($search = NULL) {

		if($search === NULL) {
			$this->template->error = 'Není co hledat!';
		} else {
			$result = $this->posts->fulltextSearch($search);
			if(count($result) == 0) {
				$this->template->search = $search;
				$this->template->error = 'Nic nebylo nalezeno';
			} else {
				$this->template->search = $search;
				$this->template->result = $result;
			}
		}
	}

}