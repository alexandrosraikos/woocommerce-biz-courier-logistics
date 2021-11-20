<?php
/**
 * The exception for missing product delegate permissions.
 * 
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.4.0
 */
class WCBizCourierLogisticsProductDelegateNotAllowedException extends Exception
{
	public function __construct(string $product_title)
	{
		parent::__construct(
			sprintf(
				__(
					"The product \"%s\" is not enabled for Biz Warehouse synchronization.",
					'wc-biz-courier-logistics'
				),
				$product_title
			),
			0,
			null
		);
	}
}

/**
 * The exception for unsupported values.
 * 
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.4.0
 */
 class WCBizCourierLogisticsUnsupportedValueException extends Exception
{
	public function __construct(string $value)
	{
		parent::__construct(
			sprintf(
				__(
					"The value \"%s\" is not supported.",
					'wc-biz-courier-logistics'
				),
				$value
			),
			0,
			null
		);
	}
}