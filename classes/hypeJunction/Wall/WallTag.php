<?php

namespace hypeJunction\Wall;

use ElggObject;

/** @access private */
class WallTag extends ElggObject {

	const TYPE = 'object';
	const SUBTYPE = 'wall_tag';

	/**
	 * {@inheritdoc}
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();
		$this->attributes['subtype'] = self::SUBTYPE;
	}
}
