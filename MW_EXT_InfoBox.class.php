<?php

namespace MediaWiki\Extension\MW_EXT_InfoBox;

use OutputPage;
use Parser;
use PPFrame;
use Skin;

/**
 * Class MW_EXT_InfoBox
 * ------------------------------------------------------------------------------------------------------------------ */
class MW_EXT_InfoBox {

	/**
	 * Clear DATA (escape html).
	 *
	 * @param $string
	 *
	 * @return string
	 * -------------------------------------------------------------------------------------------------------------- */

	private static function clearData( $string ) {
		$outString = htmlspecialchars( trim( $string ), ENT_QUOTES );

		return $outString;
	}

	/**
	 * Convert DATA (replace space & lower case).
	 *
	 * @param $string
	 *
	 * @return string
	 * -------------------------------------------------------------------------------------------------------------- */

	private static function convertData( $string ) {
		$outString = mb_strtolower( str_replace( ' ', '-', $string ), 'UTF-8' );

		return $outString;
	}

	/**
	 * Wiki Framework message.
	 *
	 * @param $string
	 *
	 * @return string
	 * -------------------------------------------------------------------------------------------------------------- */

	private static function getMsgText( $string ) {
		$outString = wfMessage( 'mw-ext-infobox-' . $string )->inContentLanguage()->text();

		return $outString;
	}

	/**
	 * Get JSON data.
	 *
	 * @return mixed
	 * -------------------------------------------------------------------------------------------------------------- */

	private static function getData() {
		$getData = file_get_contents( __DIR__ . '/storage/infobox.json' );
		$outData = json_decode( $getData, true );

		return $outData;
	}

	/**
	 * Get type.
	 *
	 * @param $type
	 *
	 * @return mixed
	 * -------------------------------------------------------------------------------------------------------------- */

	private static function getType( $type ) {
		$getData = self::getData();

		if ( ! isset( $getData['infobox'][ $type ] ) ) {
			return false;
		}

		$getType = $getData['infobox'][ $type ];
		$outType = $getType;

		return $outType;
	}

	/**
	 * Get icon.
	 *
	 * @param $type
	 *
	 * @return mixed
	 * -------------------------------------------------------------------------------------------------------------- */

	private static function getTypeIcon( $type ) {
		$type = self::getType( $type ) ? self::getType( $type ) : '';

		if ( ! isset( $type['icon'] ) ) {
			return false;
		}

		$getIcon = $type['icon'];
		$outIcon = $getIcon;

		return $outIcon;
	}

	/**
	 * Get type property.
	 *
	 * @param $type
	 *
	 * @return mixed
	 * -------------------------------------------------------------------------------------------------------------- */

	private static function getTypeProperty( $type ) {
		$type = self::getType( $type ) ? self::getType( $type ) : '';

		if ( ! isset( $type['property'] ) ) {
			return false;
		}

		$getProperty = $type['property'];
		$outProperty = $getProperty;

		return $outProperty;
	}

	/**
	 * Get field.
	 *
	 * @param $type
	 * @param $field
	 *
	 * @return mixed
	 * -------------------------------------------------------------------------------------------------------------- */

	private static function getField( $type, $field ) {
		$type = self::getType( $type ) ? self::getType( $type ) : '';

		if ( ! isset( $type['field'][ $field ] ) ) {
			return false;
		}

		$getField = $type['field'][ $field ];
		$outField = $getField;

		return $outField;
	}

	/**
	 * Get field property.
	 *
	 * @param $type
	 * @param $field
	 *
	 * @return mixed
	 * -------------------------------------------------------------------------------------------------------------- */

	private static function getFieldProperty( $type, $field ) {
		$field = self::getField( $type, $field ) ? self::getField( $type, $field ) : '';

		if ( ! isset( $field['property'] ) ) {
			return false;
		}

		$getProperty = $field['property'];
		$outProperty = $getProperty;

		return $outProperty;
	}

	/**
	 * Register tag function.
	 *
	 * @param Parser $parser
	 *
	 * @return bool
	 * @throws \MWException
	 * -------------------------------------------------------------------------------------------------------------- */

	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'infobox', __CLASS__ . '::onRenderTag', Parser::SFH_OBJECT_ARGS );

		return true;
	}

	/**
	 * Render tag function.
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 *
	 * @return null|string
	 * -------------------------------------------------------------------------------------------------------------- */

	public static function onRenderTag( Parser $parser, PPFrame $frame, array $args ) {
		// Get options parser.
		$getOptions = self::extractOptions( $args, $frame );

		// Argument: type.
		$getBoxType = self::clearData( $getOptions['type'] ?? '' ?: '' );
		$outBoxType = empty( $getBoxType ) ? '' : self::convertData( $getBoxType );

		// Argument: title.
		$getItemTitle = self::clearData( $getOptions['title'] ?? '' ?: '' );
		$outItemTitle = empty( $getItemTitle ) ? self::getMsgText( 'block-title' ) : $getItemTitle;

		// Argument: image.
		$getItemImage = self::clearData( $getOptions['image'] ?? '' ?: '' );

		// Argument: caption.
		$getItemCaption = self::clearData( $getOptions['caption'] ?? '' ?: '' );
		$outItemCaption = empty( $getItemCaption ) ? '' : '<div>' . $getItemCaption . '</div>';

		// Out item type.
		$outItemType = empty( $getBoxType ) ? '' : self::convertData( $getBoxType );

		// Check infobox type, set error category.
		if ( ! self::getType( $outBoxType ) ) {
			$parser->addTrackingCategory( 'mw-ext-infobox-error-category' );

			return null;
		}

		// Check infobox property.
		if ( self::getTypeProperty( $outBoxType ) ) {
			$typeProperty = self::getTypeProperty( $outBoxType );
		} else {
			$typeProperty = '';
		}

		// Out image or icon.
		$outItemImage = empty( $getItemImage ) ? '<i class="' . self::getTypeIcon( $outBoxType ) . '"></i>' : $getItemImage;

		// Out HTML.
		$outHTML = '<div class="mw-ext-infobox mw-ext-infobox-' . $outBoxType . ' navigation-not-searchable" itemscope itemtype="http://schema.org/' . $typeProperty . '">';
		$outHTML .= '<div class="infobox-item infobox-item-title"><div>' . $outItemTitle . '</div><div>' . self::getMsgText( $outItemType ) . '</div></div>';
		$outHTML .= '<div class="infobox-item infobox-item-image"><div>' . $outItemImage . '</div>' . $outItemCaption . '</div>';

		foreach ( $getOptions as $key => $value ) {
			$key   = self::convertData( $key );
			$field = self::getField( $outBoxType, $key );
			$title = $outBoxType . '-' . self::convertData( $key );

			if ( self::getFieldProperty( $outBoxType, $key ) ) {
				$fieldProperty = self::getFieldProperty( $outBoxType, $key );
			} else {
				$fieldProperty = '';
			}

			if ( $field && ! empty( $value ) ) {
				$outHTML .= '<div class="infobox-grid infobox-item infobox-item-' . $title . '">';
				$outHTML .= '<div class="item-title">' . self::getMsgText( $title ) . '</div>';
				$outHTML .= '<div class="item-value" itemprop="' . $fieldProperty . '">' . self::clearData( $value ) . '</div>';
				$outHTML .= '</div>';
			}
		}

		$outHTML .= '</div>';

		// Out parser.
		$outParser = $outHTML;

		return $outParser;
	}

	/**
	 * Converts an array of values in form [0] => "name=value" into a real
	 * associative array in form [name] => value. If no = is provided,
	 * true is assumed like this: [name] => true.
	 *
	 * @param array $options
	 * @param PPFrame $frame
	 *
	 * @return array
	 * -------------------------------------------------------------------------------------------------------------- */

	private static function extractOptions( array $options, PPFrame $frame ) {
		$results = [];

		foreach ( $options as $option ) {
			$pair = explode( '=', $frame->expand( $option ), 2 );

			if ( count( $pair ) === 2 ) {
				$name             = self::clearData( $pair[0] );
				$value            = self::clearData( $pair[1] );
				$results[ $name ] = $value;
			}

			if ( count( $pair ) === 1 ) {
				$name             = self::clearData( $pair[0] );
				$results[ $name ] = true;
			}
		}

		return $results;
	}

	/**
	 * Load resource function.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 *
	 * @return bool
	 * -------------------------------------------------------------------------------------------------------------- */

	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$out->addModuleStyles( [ 'ext.mw.infobox.styles' ] );

		return true;
	}
}
