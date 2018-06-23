<?php

namespace MediaWiki\Extension\MW_EXT_InfoBox;

use OutputPage, Parser, PPFrame, Skin;
use MediaWiki\Extension\MW_EXT_Core\MW_EXT_Core;

/**
 * Class MW_EXT_InfoBox
 * ------------------------------------------------------------------------------------------------------------------ */
class MW_EXT_InfoBox {

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

	public static function onRenderTag( Parser $parser, PPFrame $frame, $args = [] ) {
		// Get options parser.
		$getOption = MW_EXT_Core::extractOptions( $args, $frame );

		// Argument: type.
		$getBoxType = MW_EXT_Core::outClear( $getOption['type'] ?? '' ?: '' );
		$outBoxType = empty( $getBoxType ) ? '' : MW_EXT_Core::outConvert( $getBoxType );

		// Argument: title.
		$getItemTitle = MW_EXT_Core::outClear( $getOption['title'] ?? '' ?: '' );
		$outItemTitle = empty( $getItemTitle ) ? MW_EXT_Core::getMessageText( 'infobox', 'block-title' ) : $getItemTitle;

		// Argument: image.
		$getItemImage = MW_EXT_Core::outClear( $getOption['image'] ?? '' ?: '' );

		// Argument: caption.
		$getItemCaption = MW_EXT_Core::outClear( $getOption['caption'] ?? '' ?: '' );
		$outItemCaption = empty( $getItemCaption ) ? '' : '<div>' . $getItemCaption . '</div>';

		// Out item type.
		$outItemType = empty( $getBoxType ) ? '' : MW_EXT_Core::outConvert( $getBoxType );

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
		$outHTML .= '<div class="infobox-item infobox-item-title"><div>' . $outItemTitle . '</div><div>' . MW_EXT_Core::getMessageText( 'infobox', $outItemType ) . '</div></div>';
		$outHTML .= '<div class="infobox-item infobox-item-image"><div>' . $outItemImage . '</div>' . $outItemCaption . '</div>';

		foreach ( $getOption as $key => $value ) {
			$key   = MW_EXT_Core::outConvert( $key );
			$field = self::getField( $outBoxType, $key );
			$title = $outBoxType . '-' . MW_EXT_Core::outConvert( $key );

			if ( self::getFieldProperty( $outBoxType, $key ) ) {
				$fieldProperty = self::getFieldProperty( $outBoxType, $key );
			} else {
				$fieldProperty = '';
			}

			if ( $field && ! empty( $value ) ) {
				$outHTML .= '<div class="infobox-grid infobox-item infobox-item-' . $title . '">';
				$outHTML .= '<div class="item-title">' . MW_EXT_Core::getMessageText( 'infobox', $title ) . '</div>';
				$outHTML .= '<div class="item-value" itemprop="' . $fieldProperty . '">' . MW_EXT_Core::outClear( $value ) . '</div>';
				$outHTML .= '</div>';
			}
		}

		$outHTML .= '</div>';

		// Out parser.
		$outParser = $outHTML;

		return $outParser;
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
