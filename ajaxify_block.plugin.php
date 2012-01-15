<?php

class AjaxifyBlockPlugin extends Plugin
{
	public function filter_get_blocks($blocks, $area, $scope_id, $theme)
	{
		foreach($blocks as $key => $block) {
			$block->_scope_id = $scope_id;
			$blocks[$key]->_ajax_url = URL::get('ajax', array('context' => 'block', '_b' => $block->id));
		}
		return $blocks;
	}

	public function action_block_content_9(Block $block, $theme)
	{
		if($block->_ajax) {
			$_SESSION['ajax_blocks'][$block->id] = $block;
		}
	}

	public function action_ajax_block(AjaxHandler $handler)
	{
		if(!isset($_SESSION['ajax_blocks'][$_GET['_b']])) die();
		$block = $_SESSION['ajax_blocks'][$_GET['_b']];

		$context = null;

		$handler->setup_theme();
		$theme = $handler->theme;

		$blocks = $theme->get_blocks($block->_area, $block->_scope_id, $theme);

		$blocks = array_filter($blocks, function($b) use ($block) {return $b->id == $block->id;});
		$rebuildblock = reset($blocks);

		$rebuildblock->_area = $block->_area;
		$rebuildblock->_instance_id = $block->_instance_id;
		$rebuildblock->_area_index = $block->_area_index;

		$hook = 'block_content_' . $rebuildblock->type;
		Plugins::act( $hook, $rebuildblock, $theme );
		Plugins::act( 'block_content', $rebuildblock, $theme );

		$rebuildblock->_content = $theme->content( $rebuildblock, $context );

		$rebuildblock->_first = $block->_first;
		$rebuildblock->_last = $block->_last;

		// Set up the theme for the wrapper
		$theme->block = $rebuildblock;
		$theme->content = $rebuildblock->_content;
		// This is the block wrapper fallback template list
		$fallback = array(
			$block->area . '.blockwrapper',
			'blockwrapper',
			'content',
		);
		if(!is_null($context)) {
			array_unshift($fallback, $context . '.blockwrapper');
			array_unshift($fallback, $context . '.' . $block->area . '.blockwrapper');
		}
		$output = $theme->display_fallback( $fallback, 'fetch' );

		echo $output;
	}

	public function action_modify_form($form)
	{
		if($form->formtype = 'block') {
			$form->block_admin->append( 'checkbox', '_show_reload', $block, _t( 'Show Ajax Reload Link:' ) );
		}
	}

}

?>