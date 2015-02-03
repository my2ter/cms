<?php
/**
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.* _
@license http://buildwithcraft.com/license*/

namespace craft\app\i18n;

use Craft;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\IOHelper;
use DateTime;
use IntlDateFormatter;
use NumberFormatter;
use yii\base\InvalidParamException;
use yii\base\Object;

/**
 * Stores locale info.
 *
 * @property string $displayName The locale’s display name.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Locale extends Object
{
	// Constants
	// =========================================================================


	/**
	 * @var int Positive prefix.
	 */
	const ATTR_POSITIVE_PREFIX = 0;

	/**
	 * @var int Positive suffix.
	 */
	const ATTR_POSITIVE_SUFFIX = 1;

	/**
	 * @var int Negative prefix.
	 */
	const ATTR_NEGATIVE_PREFIX = 2;

	/**
	 * @var int Negative suffix.
	 */
	const ATTR_NEGATIVE_SUFFIX = 3;

	/**
	 * @var int The character used to pad to the format width.
	 */
	const ATTR_PADDING_CHARACTER = 4;

	/**
	 * @var int The ISO currency code.
	 */
	const ATTR_CURRENCY_CODE = 5;

	/**
	 * @var int The default rule set. This is only available with rule-based
	 * formatters.
	 */
	const ATTR_DEFAULT_RULESET = 6;

	/**
	 * @var int The public rule sets. This is only available with rule-based
	 * formatters. This is a read-only attribute. The public rulesets are
	 * returned as a single string, with each ruleset name delimited by ';'
	 * (semicolon).
	 */
	const ATTR_PUBLIC_RULESETS = 7;

	/**
	 * @var int The decimal separator.
	 */
	const SYMBOL_DECIMAL_SEPARATOR = 0;

	/**
	 * @var int The grouping separator.
	 */
	const SYMBOL_GROUPING_SEPARATOR = 1;

	/**
	 * @var int The pattern separator.
	 */
	const SYMBOL_PATTERN_SEPARATOR = 2;

	/**
	 * @var int The percent sign.
	 */
	const SYMBOL_PERCENT = 3;

	/**
	 * @var int Zero.
	 */
	const SYMBOL_ZERO_DIGIT = 4;

	/**
	 * @var int Character representing a digit in the pattern.
	 */
	const SYMBOL_DIGIT = 5;

	/**
	 * @var int The minus sign.
	 */
	const SYMBOL_MINUS_SIGN = 6;

	/**
	 * @var int The plus sign.
	 */
	const SYMBOL_PLUS_SIGN = 7;

	/**
	 * @var int The currency symbol.
	 */
	const SYMBOL_CURRENCY = 8;

	/**
	 * @var int The international currency symbol.
	 */
	const SYMBOL_INTL_CURRENCY = 9;

	/**
	 * @var int The monetary separator.
	 */
	const SYMBOL_MONETARY_SEPARATOR = 10;

	/**
	 * @var int The exponential symbol.
	 */
	const SYMBOL_EXPONENTIAL = 11;

	/**
	 * @var int Per mill symbol.
	 */
	const SYMBOL_PERMILL = 12;

	/**
	 * @var int Escape padding character.
	 */
	const SYMBOL_PAD_ESCAPE = 13;

	/**
	 * @var int Infinity symbol.
	 */
	const SYMBOL_INFINITY = 14;

	/**
	 * @var int Not-a-number symbol.
	 */
	const SYMBOL_NAN = 15;

	/**
	 * @var int Significant digit symbol.
	 */
	const SYMBOL_SIGNIFICANT_DIGIT = 16;

	/**
	 * @var int The monetary grouping separator.
	 */
	const SYMBOL_MONETARY_GROUPING_SEPARATOR = 17;

	/**
	 * @var int The short date/time format.
	 */
	const FORMAT_ABBREVIATED = 4;

	/**
	 * @var int The short date/time format.
	 */
	const FORMAT_SHORT = 3;

	/**
	 * @var int The medium date/time format.
	 */
	const FORMAT_MEDIUM = 2;

	/**
	 * @var int The long date/time format.
	 */
	const FORMAT_LONG = 1;

	/**
	 * @var int The full date/time format.
	 */
	const FORMAT_FULL = 0;

	// Properties
	// =========================================================================

	/**
	 * @var string The locale ID.
	 */
	public $id;

	/**
	 * @var boolean Whether the [PHP intl extension](http://php.net/manual/en/book.intl.php) is loaded.
	 */
	private $_intlLoaded = false;

	/**
	 * @var array The configured locale data, used if the [PHP intl extension](http://php.net/manual/en/book.intl.php) isn’t loaded.
	 */
	private $_data;

	/**
	 * @var Formatter The locale's formatter.
	 */
	private $_formatter;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor.
	 *
	 * @param string $id     The locale ID.
	 * @param array  $config Name-value pairs that will be used to initialize the object properties.
	 */
	public function __construct($id, $config = [])
	{
		$this->id = str_replace('_', '-', $id);
		$this->_intlLoaded = extension_loaded('intl');

		if (!$this->_intlLoaded)
		{
			// Load the locale data
			$appDataPath = Craft::$app->path->getAppPath().'/config/locales/'.$this->id.'.php';
			$customDataPath = Craft::$app->path->getConfigPath().'/locales/'.$this->id.'.php';

			if (IOHelper::fileExists($appDataPath))
			{
				$this->_data = require($appDataPath);
			}

			if (IOHelper::fileExists($customDataPath))
			{
				if ($this->_data !== null)
				{
					$this->_data = ArrayHelper::merge($this->_data, require($customDataPath));
				}
				else
				{
					$this->_data = require($customDataPath);
				}
			}

			if ($this->_data === null)
			{
				throw new InvalidParamException('Unsupported locale: '.$this->id);
			}
		}

		parent::__construct($config);
	}

	/**
	 * Use the ID as the string representation of locales.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->id;
	}

	/**
	 * Returns this locale’s language ID.
	 *
	 * @return string This locale’s language ID.
	 */
	public function getLanguageID()
	{
		if (($pos = strpos($this->id, '-')) !== false)
		{
			return substr($this->id, 0, $pos);
		}

		return $this->id;
	}

	/**
	 * Returns this locale’s script ID.
	 *
	 * A script ID consists of only the last four characters after a dash in the locale ID.
	 *
	 * @return string|null The locale’s script ID, if it has one.
	 */
	public function getScriptID()
	{
		// Find sub tags
		if (($pos = strpos($this->id, '-')) !== false)
		{
			$subTag = explode('-', $this->id);

			// Script sub tags can be distinguished from territory sub tags by length
			if (strlen($subTag[1]) === 4)
			{
				return $subTag[1];
			}
		}
	}

	/**
	 * Returns this locale’s territory ID.
	 *
	 * A territory ID consists of only the last two to three letter or digits after a dash in the locale ID.
	 *
	 * @return string|null The locale’s territory ID, if it has one.
	 */
	public function getTerritoryID()
	{
		// Find sub tags
		if (($pos = strpos($this->id, '-')) !== false)
		{
			$subTag = explode('-', $this->id);

			// Territory sub tags can be distinguished from script sub tags by length
			if (isset($subTag[2]) && strlen($subTag[2]) < 4)
			{
				return $subTag[2];
			}
			else if (strlen($subTag[1]) < 4)
			{
				return $subTag[1];
			}
		}
	}

	/**
	 * Returns the locale name in a given language.
	 *
	 * @param string|null $inLocale
	 * @return string
	 */
	public function getDisplayName($inLocale = null)
	{
		// If no target locale is specified, default to this locale
		if (!$inLocale)
		{
			$inLocale = $this->id;
		}

		if ($this->_intlLoaded)
		{
			return \Locale::getDisplayName($this->id, $inLocale);
		}
		else if ($this->id === 'en')
		{
			return 'English';
		}
		else
		{
			return $this->id;
		}
	}

	/**
	 * Returns a [[Formatter]] for this locale.
	 *
	 * @return Formatter A formatter for this locale.
	 */
	public function getFormatter()
	{
		if ($this->_formatter === null)
		{
			$config = [
				'locale' => $this->id,
			];

			if (!$this->_intlLoaded)
			{
				$config['dateTimeFormats']   = $this->_data['dateTimeFormats'];
				$config['currencySymbols']   = $this->_data['currencySymbols'];
				$config['decimalSeparator']  = $this->getNumberSymbol(static::SYMBOL_DECIMAL_SEPARATOR);
				$config['thousandSeparator'] = $this->getNumberSymbol(static::SYMBOL_GROUPING_SEPARATOR);
				$config['currencyCode']      = $this->getNumberSymbol(static::SYMBOL_INTL_CURRENCY);
			}

			$this->_formatter = new Formatter($config);
		}

		return $this->_formatter;
	}

	// Date/Time Formatting
	// -------------------------------------------------------------------------

	/**
	 * Returns the localized PHP date format.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_SHORT, ::MEDIUM, ::LONG, ::FULL
	 * @return string The localized PHP date format.
	 */
	public function getDateFormat($length = null)
	{
		return $this->_getDateTimeFormat($length, true, false);
	}

	/**
	 * Returns the localized PHP time format.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_SHORT, ::MEDIUM, ::LONG, ::FULL
	 * @return string The localized PHP time format.
	 */
	public function getTimeFormat($length = null)
	{
		return $this->_getDateTimeFormat($length, false, true);
	}

	/**
	 * Returns the localized PHP date + time format.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_SHORT, ::MEDIUM, ::LONG, ::FULL
	 * @return string The localized PHP date + time format.
	 */
	public function getDateTimeFormat($length = null)
	{
		return $this->_getDateTimeFormat($length, true, true);
	}

	/**
	 * Returns a localized month name.
	 *
	 * @param int $month  The month to return (1-12).
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_ABBREVIATED, ::MEDIUM, ::FULL
	 * @return string The localized month name.
	 */
	public function getMonthName($month, $length = null)
	{
		if ($length === null)
		{
			$length = static::FORMAT_FULL;
		}

		if ($this->_intlLoaded)
		{
			$formatter = new IntlDateFormatter($this->id, IntlDateFormatter::NONE, IntlDateFormatter::NONE);

			switch ($length)
			{
				case static::FORMAT_ABBREVIATED: $formatter->setPattern('LLLLL'); break; // S
				case static::FORMAT_SHORT:
				case static::FORMAT_MEDIUM:      $formatter->setPattern('LLL'); break;   // Sep
				default:                         $formatter->setPattern('LLLL'); break;  // September
			}

			return $formatter->format(new DateTime('1970-'.sprintf("%02d", $month).'-01'));
		}
		else
		{
			switch ($length)
			{
				case static::FORMAT_ABBREVIATED: return $this->_data['monthNames']['abbreviated'][$month-1]; break; // S
				case static::FORMAT_SHORT:
				case static::FORMAT_MEDIUM:      return $this->_data['monthNames']['medium'][$month-1]; break;      // Sep
				default:                         return $this->_data['monthNames']['full'][$month-1]; break;        // September
			}
		}
	}

	/**
	 * Returns all of the localized month names.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_ABBREVIATED, ::MEDIUM, ::FULL
	 * @return array The localized month names.
	 */
	public function getMonthNames($length = null)
	{
		$monthNames = [];

		for ($month = 1; $month <= 12; $month++)
		{
			$monthNames[] = $this->getMonthName($month, $length);
		}

		return $monthNames;
	}

	/**
	 * Returns a localized day of the week name.
	 *
	 * @param int $day    The day of the week to return (1-7), where 1 stands for Sunday.
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_ABBREVIATED, ::SHORT, ::MEDIUM, ::FULL
	 * @return string The localized day of the week name.
	 */
	public function getWeekDayName($day, $length = null)
	{
		if ($length === null)
		{
			$length = static::FORMAT_FULL;
		}

		if ($this->_intlLoaded)
		{
			$formatter = new IntlDateFormatter($this->id, IntlDateFormatter::NONE, IntlDateFormatter::NONE);

			switch ($length)
			{
				case static::FORMAT_ABBREVIATED: $formatter->setPattern('ccccc'); break;  // T
				case static::FORMAT_SHORT:       $formatter->setPattern('cccccc'); break; // Tu
				case static::FORMAT_MEDIUM:      $formatter->setPattern('ccc'); break;    // Tue
				default:                         $formatter->setPattern('cccc'); break;   // Tuesday
			}

			// Jan 1, 1970 was a Thursday
			return $formatter->format(new DateTime('1970-01-'.sprintf("%02d", $day+3)));
		}
		else
		{
			switch ($length)
			{
				case static::FORMAT_ABBREVIATED: return $this->_data['weekDayNames']['abbreviated'][$day-1]; break; // T
				case static::FORMAT_SHORT:       return $this->_data['weekDayNames']['short'][$day-1]; break;       // Tu
				case static::FORMAT_MEDIUM:      return $this->_data['weekDayNames']['medium'][$day-1]; break;      // Tue
				default:                         return $this->_data['weekDayNames']['full'][$day-1]; break;        // Tuesday
			}
		}
	}

	/**
	 * Returns all of the localized day of the week names.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_ABBREVIATED, ::MEDIUM, ::FULL
	 * @return array The localized day of the week names.
	 */
	public function getWeekDayNames($length = null)
	{
		$weekDayNames = [];

		for ($day = 1; $day <= 7; $day++)
		{
			$weekDayNames[] = $this->getWeekDayName($day, $length);
		}

		return $weekDayNames;
	}

	/**
	 * Returns the "AM" name for this locale.
	 *
	 * @return string The "AM" name.
	 */
	public function getAMName()
	{
		if ($this->_intlLoaded)
		{
			return $this->getFormatter()->asDate(new DateTime('00:00'), 'a');
		}
		else
		{
			return $this->_data['amName'];
		}
	}

	/**
	 * Returns the "PM" name for this locale.
	 *
	 * @return string The "PM" name.
	 */
	public function getPMName()
	{
		if ($this->_intlLoaded)
		{
			return $this->getFormatter()->asDate(new DateTime('12:00'), 'a');
		}
		else
		{
			return $this->_data['pmName'];
		}
	}

	// Text Attributes and Symbols
	// -------------------------------------------------------------------------

	/**
	 * Returns a text attribute used by this locale.
	 *
	 * @param int $attribute The attribute to return. Values: Locale::
	 * @return string The attribute.
	 */
	public function getTextAttribute($attribute)
	{
		if ($this->_intlLoaded)
		{
			$formatter = new NumberFormatter($this->id, NumberFormatter::DECIMAL);
			return $formatter->getTextAttribute($attribute);
		}
		else
		{
			switch ($attribute)
			{
				case static::ATTR_POSITIVE_PREFIX: return $this->_data['textAttributes']['positivePrefix'];
				case static::ATTR_POSITIVE_SUFFIX: return $this->_data['textAttributes']['positiveSuffix'];
				case static::ATTR_NEGATIVE_PREFIX: return $this->_data['textAttributes']['negativePrefix'];
				case static::ATTR_NEGATIVE_SUFFIX: return $this->_data['textAttributes']['negativeSuffix'];
				case static::ATTR_PADDING_CHARACTER: return $this->_data['textAttributes']['paddingCharacter'];
				case static::ATTR_CURRENCY_CODE: return $this->_data['textAttributes']['currencyCode'];
				case static::ATTR_DEFAULT_RULESET: return $this->_data['textAttributes']['defaultRuleset'];
				case static::ATTR_PUBLIC_RULESETS: return $this->_data['textAttributes']['publicRulesets'];
			}
		}
	}

	/**
	 * Returns a number symbol used by this locale.
	 *
	 * @param int $symbol The symbol to return. Values: Locale::SYMBOL_DECIMAL_SEPARATOR, ::SYMBOL_GROUPING_SEPARATOR,
	 *                    ::SYMBOL_PATTERN_SEPARATOR, ::SYMBOL_PERCENT, ::SYMBOL_ZERO_DIGIT, ::SYMBOL_DIGIT, ::SYMBOL_MINUS_SIGN,
	 *                    ::SYMBOL_PLUS_SIGN, ::SYMBOL_CURRENCY, ::SYMBOL_INTL_CURRENCY, ::SYMBOL_MONETARY_SEPARATOR,
	 *                    ::SYMBOL_EXPONENTIAL, ::SYMBOL_PERMILL, ::SYMBOL_PAD_ESCAPE, ::SYMBOL_INFINITY, ::SYMBOL_NAN,
	 *                    ::SYMBOL_SIGNIFICANT_DIGIT, ::SYMBOL_MONETARY_GROUPING_SEPARATOR
	 * @return string The symbol.
	 */
	public function getNumberSymbol($symbol)
	{
		if ($this->_intlLoaded)
		{
			$formatter = new NumberFormatter($this->id, NumberFormatter::DECIMAL);
			return $formatter->getSymbol($symbol);
		}
		else
		{
			switch ($symbol)
			{
				case static::SYMBOL_DECIMAL_SEPARATOR: return $this->_data['numberSymbols']['decimalSeparator'];
				case static::SYMBOL_GROUPING_SEPARATOR: return $this->_data['numberSymbols']['groupingSeparator'];
				case static::SYMBOL_PATTERN_SEPARATOR: return $this->_data['numberSymbols']['patternSeparator'];
				case static::SYMBOL_PERCENT: return $this->_data['numberSymbols']['percent'];
				case static::SYMBOL_ZERO_DIGIT: return $this->_data['numberSymbols']['zeroDigit'];
				case static::SYMBOL_DIGIT: return $this->_data['numberSymbols']['digit'];
				case static::SYMBOL_MINUS_SIGN: return $this->_data['numberSymbols']['minusSign'];
				case static::SYMBOL_PLUS_SIGN: return $this->_data['numberSymbols']['plusSign'];
				case static::SYMBOL_CURRENCY: return $this->_data['numberSymbols']['currency'];
				case static::SYMBOL_INTL_CURRENCY: return $this->_data['numberSymbols']['intlCurrency'];
				case static::SYMBOL_MONETARY_SEPARATOR: return $this->_data['numberSymbols']['monetarySeparator'];
				case static::SYMBOL_EXPONENTIAL: return $this->_data['numberSymbols']['exponential'];
				case static::SYMBOL_PERMILL: return $this->_data['numberSymbols']['permill'];
				case static::SYMBOL_PAD_ESCAPE: return $this->_data['numberSymbols']['padEscape'];
				case static::SYMBOL_INFINITY: return $this->_data['numberSymbols']['infinity'];
				case static::SYMBOL_NAN: return $this->_data['numberSymbols']['nan'];
				case static::SYMBOL_SIGNIFICANT_DIGIT: return $this->_data['numberSymbols']['significantDigit'];
				case static::SYMBOL_MONETARY_GROUPING_SEPARATOR: return $this->_data['numberSymbols']['monetaryGroupingSeparator'];
			}
		}
	}

	/**
	 * Returns this locale’s symbol for a given currency.
	 *
	 * @param string $currency The 3-letter ISO 4217 currency code indicating the currency to use.
	 * @return string The currency symbol.
	 */
	public function getCurrencySymbol($currency)
	{
		if ($this->_intlLoaded)
		{
			$formatter = new NumberFormatter($this->id, NumberFormatter::CURRENCY);
			$value = $formatter->formatCurrency(1, $currency);
			return trim($value, '1.0');
		}
		else if (isset($this->_data['currencySymbols'][$currency]))
		{
			return $this->_data['currencySymbols'][$currency];
		}
		else
		{
			return $currency;
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a localized PHP date/time format.
	 *
	 * @param int $length The format length that should be returned. Values: Locale::FORMAT_SHORT, ::MEDIUM, ::LONG, ::FULL
	 * @param boolean $withDate Whether the date should be included in the format.
	 * @param boolean $withTime Whether the time should be included in the format.
	 * @return string The PHP date/time format
	 */
	private function _getDateTimeFormat($length, $withDate, $withTime)
	{
		if ($length === null)
		{
			$length = static::FORMAT_MEDIUM;
		}

		if ($this->_intlLoaded)
		{
			$dateType = ($withDate ? $length : IntlDateFormatter::NONE);
			$timeType = ($withTime ? $length : IntlDateFormatter::NONE);
			$formatter = new IntlDateFormatter($this->id, $dateType, $timeType);
			return $formatter->getPattern();
		}
		else
		{
			if ($withDate && $withTime)
			{
				$which = 'datetime';
			}
			else if ($withDate)
			{
				$which = 'date';
			}
			else
			{
				$which = 'time';
			}

			switch ($length)
			{
				case static::FORMAT_SHORT:  return $this->_data['dateTimeFormats']['short'][$which]; break;
				case static::FORMAT_MEDIUM: return $this->_data['dateTimeFormats']['medium'][$which]; break;
				case static::FORMAT_LONG:   return $this->_data['dateTimeFormats']['long'][$which]; break;
				case static::FORMAT_FULL:   return $this->_data['dateTimeFormats']['full'][$which]; break;
			}
		}
	}
}