<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Utility
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Inspiration Utility class.
 *
 * @since 1.0.10
 */
class Boldgrid_Inspirations_Utility {

	/**
	 * Does $haystack end with $needle?
	 *
	 * @param string $haystack A string to be searched.
	 * @param string $needle A search string.
	 * @return boolean
	 */
	public static function endsWith( $haystack, $needle ) {
		$length = strlen( $needle );

		if ( 0 === $length ) {
			return true;
		}

		return ( substr( $haystack, - $length ) === $needle );
	}

	/**
	 * This function allows you to easily include an inline js file.
	 *
	 * All js files must be located within the assets/js/inline folder.
	 *
	 * @param string $filename A filename.
	 */
	public static function inline_js_file( $filename ) {
		$full_path_to_js = plugins_url( '/assets/js/inline/' . $filename,
		BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php' );

		echo '<script type="text/javascript" src="' . $full_path_to_js;

		if ( defined( 'BOLDGRID_INSPIRATIONS_VERSION' ) ) {
			echo '?ver=' . BOLDGRID_INSPIRATIONS_VERSION;
		}

		echo '"></script>';
	}

	/**
	 * Similar to inline_js_file(), except this allows you to run oneliners when a file cannot be used.
	 *
	 * @param string $oneliner A block of code.
	 */
	public static function inline_js_oneliner( $oneliner ) {
		echo '
		<script type="text/javascript">
			' . $oneliner . '
		</script>
	';
	}

	/**
	 * Does $haystack start with $needle?
	 *
	 * @param string $haystack A string to be searched.
	 * @param string $needle A search string.
	 * @return boolean
	 */
	public static function startsWith( $haystack, $needle ) {
		$length = strlen( $needle );

		return ( substr( $haystack, 0, $length ) === $needle );
	}

	/**
	 * Is binary.
	 *
	 * Check if a string is binary.
	 *
	 * @since 1.2
	 *
	 * @param string $string A string to test.
	 * @return bool
	 */
	public static function is_binary( $string = null ) {
		if ( null === $string && 0 === strlen( $string ) ) {
			return false;
		}

		// Split the string into a character array.
		$string_array = str_split( $string );

		foreach ( $string_array as $char ) {
			// Get the ASCII code.
			$ascii = ord( $char );

			// Check range: (32-126, 7 (TAB), 10 (LF), and 13 (CR)) are not binary.
			if ( ( $ascii < 32 || $ascii > 126 ) && 7 !== $ascii && 10 !== $ascii && 13 !== $ascii ) {
				// A binary character was found.
				return true;
			}
		}

		return false;
	}

	/**
	 * Read an include file and set it into a variable.
	 *
	 * @since 1.2.5
	 *
	 * @static
	 *
	 * @param string $file A file to parse.
	 * @return string The markup.
	 */
	public static function file_to_var( $file ) {
		ob_start();
		include $file;
		return ob_get_clean();
	}

	/**
	 * Convert content encoding from "UTF-8" to "HTML-ENTITIES".
	 *
	 * If mbstring is not loaded in PHP then the input will be returned unconverted.
	 *
	 * @since 1.2.5
	 *
	 * @static
	 *
	 * @param string $input Content to be converted.
	 * @return string Content that may have been converted.
	 */
	public static function utf8_to_html( $input ) {
		if( function_exists( 'mb_convert_encoding' ) ){
			return mb_convert_encoding( $input, 'HTML-ENTITIES', 'UTF-8' );
		} else {
			return $input;
		}
	}
}
