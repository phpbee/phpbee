<?php

function smarty_modifier_word_ogo($string)
{
	$words=array_map('trim',explode(' ',$string));
	$new_words=array();
	foreach ($words as $key => $word) {
		$new_words[$key]=conjugate_word($word);
	}
	return implode(' ',$new_words);
} 

function conjugate_word($word) {
	$patterns=array(
		'/ая$/',
		'/ь$/',
	);
	$replacements=array(
		'ой',
		'и',
	);
	return preg_replace($patterns, $replacements, $word);
}