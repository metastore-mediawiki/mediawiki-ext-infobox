<?php

namespace MediaWiki\Extension\MetaStore;

use OutputPage, Parser, PPFrame, Skin;

/**
 * Class MW_EXT_InfoBox
 */
class MW_EXT_InfoBox {

	/**
	 * Get type.
	 *
	 * @param $type
	 *
	 * @return array
	 */
	private static function getType( $type ) {
		$get = MW_EXT_Kernel::getJSON( __DIR__ . '/storage/infobox.json' );
		$out = $get['infobox'][ $type ] ?? [] ?: [];

		return $out;
	}

	/**
	 * Get icon.
	 *
	 * @param $type
	 *
	 * @return mixed|string
	 */
	private static function getTypeIcon( $type ) {
		$type = self::getType( $type ) ? self::getType( $type ) : '';
		$out  = $type['icon'] ?? '' ?: '';

		return $out;
	}

	/**
	 * Get type property.
	 *
	 * @param $type
	 *
	 * @return mixed|string
	 */
	private static function getTypeProperty( $type ) {
		$type = self::getType( $type ) ? self::getType( $type ) : '';
		$out  = $type['property'] ?? '' ?: '';

		return $out;
	}

	/**
	 * Get field.
	 *
	 * @param $type
	 * @param $field
	 *
	 * @return array
	 */
	private static function getField( $type, $field ) {
		$type = self::getType( $type ) ? self::getType( $type ) : '';
		$out  = $type['field'][ $field ] ?? [] ?: [];

		return $out;
	}

	/**
	 * Get field property.
	 *
	 * @param $type
	 * @param $field
	 *
	 * @return mixed
	 */
	private static function getFieldProperty( $type, $field ) {
		$field = self::getField( $type, $field ) ? self::getField( $type, $field ) : '';
		$out   = $field['property'] ?? '' ?: '';

		return $out;
	}

	/**
	 * Register tag function.
	 *
	 * @param Parser $parser
	 *
	 * @return bool
	 * @throws \MWException
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'infobox', [ __CLASS__, 'onRenderTag' ], Parser::SFH_OBJECT_ARGS );

		return true;
	}

	/**
	 * Render tag function.
	 *
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 *
	 * @return null|string
	 */
	public static function onRenderTag( array $args, Parser $parser, PPFrame $frame ) {
		// Get options parser.
		$getOption = MW_EXT_Kernel::extractOptions( $args, $frame );

		// Argument: type.
		$getBoxType = MW_EXT_Kernel::outClear( $getOption['type'] ?? '' ?: '' );
		$outBoxType = empty( $getBoxType ) ? '' : MW_EXT_Kernel::outNormalize( $getBoxType );

		// Argument: title.
		$getItemTitle = MW_EXT_Kernel::outClear( $getOption['title'] ?? '' ?: '' );
		$outItemTitle = empty( $getItemTitle ) ? MW_EXT_Kernel::getMessageText( 'infobox', 'block-title' ) : $getItemTitle;

		// Argument: image.
		$getItemImage = MW_EXT_Kernel::outClear( $getOption['image'] ?? '' ?: '' );

		// Argument: caption.
		$getItemCaption = MW_EXT_Kernel::outClear( $getOption['caption'] ?? '' ?: '' );
		$outItemCaption = empty( $getItemCaption ) ? '' : '<div>' . $getItemCaption . '</div>';

		// Out item type.
		$outItemType = empty( $getBoxType ) ? '' : MW_EXT_Kernel::outNormalize( $getBoxType );

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
		$outHTML .= '<div class="infobox-item infobox-item-title"><div>' . $outItemTitle . '</div><div>' . MW_EXT_Kernel::getMessageText( 'infobox', $outItemType ) . '</div></div>';
		$outHTML .= '<div class="infobox-item infobox-item-image"><div>' . $outItemImage . '</div>' . $outItemCaption . '</div>';

		foreach ( $getOption as $key => $value ) {
			$key   = MW_EXT_Kernel::outNormalize( $key );
			$field = self::getField( $outBoxType, $key );
			$title = $outBoxType . '-' . MW_EXT_Kernel::outNormalize( $key );

			if ( self::getFieldProperty( $outBoxType, $key ) ) {
				$fieldProperty = self::getFieldProperty( $outBoxType, $key );
			} else {
				$fieldProperty = '';
			}

			if ( $field && ! empty( $value ) ) {
				$outHTML .= '<div class="infobox-grid infobox-item infobox-item-' . $title . '">';
				$outHTML .= '<div class="item-title">' . MW_EXT_Kernel::getMessageText( 'infobox', $title ) . '</div>';
				$outHTML .= '<div class="item-value" itemprop="' . $fieldProperty . '">' . MW_EXT_Kernel::outClear( $value ) . '</div>';
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
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$out->addModuleStyles( [ 'ext.mw.infobox.styles' ] );

		return true;
	}
}
