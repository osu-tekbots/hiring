<?php
/**
 * Generates the standard button used in the navigation bar and in other linkable areas throughout the website.
 *
 * @param string $path The URL the button should link to
 * @param string $name The name to display on the link
 * 
 * @return string The HTML for the button generated
 */
function createLinkButton($path, $name) {
    return "
	<a href='$path'>
		<button class='btn btn-outline-primary capstone-nav-btn' type='button'><h6>$name</h6></button>
	</a>
	";
}

/**
 * Generates the button used in the website's main header.
 *
 * @param string $path The URL the button should link to
 * @param string $name The name to display on the link
 * 
 * @return string The HTML for the button generated
 */
function createHeaderButton($path, $name) {
	return "
	<a href='$path'>
		<li>$name</li>
	</a>
	";
}
