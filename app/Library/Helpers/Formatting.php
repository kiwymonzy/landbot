<?php

/**
 * Format number to float with 2 decimal places
 *
 * @param mixed $amount
 * @return float
 */
function priceFormat($amount)
{
    return (float) sprintf("%.2f", $amount);
}

function currencyFormat($amount)
{
    return '$' . priceFormat($amount);
}
