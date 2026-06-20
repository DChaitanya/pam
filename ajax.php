<?php

// Set the default timezone to use. Available as of PHP 5.1
date_default_timezone_set('UTC');

if (isset($_GET['action'])) {
	// Direct AJAX calls require an authenticated session (finding S4). Pages that
	// merely include this file for its helper functions do not pass ?action and
	// therefore skip this guard.
	require_once __DIR__ . '/auth_guard.php';

	if ($_GET['action'] == 'cal_maturity_date') {

		$deposite_date = $_REQUEST['deposite_date'];
		$period = $_REQUEST['period'];
		$period_type = $_REQUEST['period_type'];

		echo $maturity_date = get_maturity_date($deposite_date, $period, $period_type);

	} elseif ($_GET['action'] == 'cal_maturity_amount') {

		$deposite_amount = $_REQUEST['deposite_amount'];
		$rate_of_interest = $_REQUEST['rate_of_interest'];
		$interest_type = $_REQUEST['interest_type'];
		$deposite_type = $_REQUEST['deposite_type'];
		$period = $_REQUEST['period'];
		$period_type = $_REQUEST['period_type'];

		$interest = get_total_interest($deposite_amount, $rate_of_interest, $period, $period_type, $deposite_type, $interest_type);
		$maturity_amount = $deposite_amount + $interest;
		echo $interest ."|". $maturity_amount;
	}
}

function get_maturity_date($renewal_date, $period, $period_type) {
	list($year, $month, $day) = explode("-", $renewal_date);

	switch ($period_type) {
		case 'd':
		case 'D':
			$day = $day + $period;
			break;
		case 'm':
		case 'M':
			$month = $month + $period;
			break;
		case 'y':
		case 'Y':
			$year = $year + $period;
			break;
	}

	return date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
}

function get_total_interest($deposite_amount, $rate_of_interest, $period, $period_type, $deposite_type, $interest_type) {

	switch ($period_type) {
		case 'm':
		case 'M':
			if ($interest_type == 0) {
				if ($deposite_type == 1) {
					// Interest Payout Monthly
					$interest = round($deposite_amount * $rate_of_interest / 100 / 12 * $period);
				} else if ($deposite_type == 2) {
					// Interest Payout Queartly
					$interest = round($deposite_amount * $rate_of_interest / 100 / 12 * $period);
				}
				$interest = round($deposite_amount * $rate_of_interest / 100 / 12 * $period);
			} else {
				$maturity_amount = $deposite_amount * pow((1 + $rate_of_interest / $interest_type / 100), ($interest_type * $period / 12)) ;
				$interest = round($maturity_amount - $deposite_amount);
			}
			break;
		case 'y':
		case 'Y':
			if ($interest_type == 0) {
				$interest = round($deposite_amount * $rate_of_interest / 100 * $period);
			} else {
				$maturity_amount = $deposite_amount * pow((1 + $rate_of_interest / $interest_type / 100), ($interest_type * $period)) ;
				$interest = round($maturity_amount - $deposite_amount);
			}
			break;
		case 'd':
		case 'D':
			if ($interest_type == 0) {
				$interest = round($deposite_amount * $rate_of_interest / 100 / 365 * $period);
			} else {
				$maturity_amount = $deposite_amount * pow((1 + $rate_of_interest / $interest_type / 100), ($interest_type * $period/365)) ;
				$interest = round($maturity_amount - $deposite_amount);
			}
			break;
	}

	return $interest;
}

function get_current_interest_value($deposite_amount, $deposite_date, $maturity_date, $today=null) {
	if (!$today) {
        $today = date('Y-m-d');
    }

	list($deposite_year, $deposite_month, $deposite_day) = explode('-', $deposite_date);
	list($today_year, $today_month, $today_day) = explode('-', $today);

	$interest_period = gregoriantojd($today_month, $today_day, $today_year) - gregoriantojd($deposite_month, $deposite_day, $deposite_year);

	$interest_period = $interest_period < 0 ? 0 : $interest_period;

	if ($interest_period <= 30) {
		// don't calculate interest for less than 30 days
		//$interest_rate = 0;

		// calculate interest from day 1
		$interest_rate = get_interest_rate($interest_period) - 1;
	} else {
		$interest_rate = get_interest_rate($interest_period) - 1;
	}
	return (int)(($deposite_amount * ($interest_rate/100/365) * $interest_period));;
}

// Revised function for calculating revised interest occured for todays or till given date for given scheme
function new_get_current_interest_value($deposite_amount, $deposite_date, $maturity_date, $interest_type, $scheme_type=null, $today=null) {
	if (!$today) {
        $today = date('Y-m-d');
    }

	list($deposite_year, $deposite_month, $deposite_day) = explode('-', $deposite_date);
	list($today_year, $today_month, $today_day) = explode('-', $today);

	$interest_period = gregoriantojd($today_month, $today_day, $today_year) - gregoriantojd($deposite_month, $deposite_day, $deposite_year);

	$interest_period = $interest_period < 0 ? 0 : $interest_period;

	$interest_rate = new_get_interest_rate($interest_period, $scheme_type) - 1;
	$interest_rate = $interest_rate < 0 ? 0 : $interest_rate;

	// $interest_type = 4;
	if ($interest_type == 0) {
		// Simple interest
		$maturity_amount = $deposite_amount * (1 + $interest_rate * $interest_period/365) ;
	} else {
		// Compound interest
		$maturity_amount = $deposite_amount * pow((1 + $interest_rate / $interest_type / 100), ($interest_type * $interest_period/365)) ;
	}
	$interest = round($maturity_amount - $deposite_amount);
	return $interest;
	// return (int)(($deposite_amount * ($interest_rate/100/365) * $interest_period));
}

function get_interest_rate($interest_period) {
	$search_query = "select interest_rate from interest_period where $interest_period >= start and $interest_period <= end";

	$db = new db();
    $rs = $db->query($search_query);
	$rs_row = mysqli_fetch_object($rs);
	$interest_rate = $rs_row->interest_rate;
	return $interest_rate;
}


function new_get_interest_rate($interest_period, $scheme_type) {
	$search_query = "select interest_rate from interest_period where scheme = $scheme_type and ($interest_period >= start and $interest_period <= end)";

	$db = new db();
    $rs = $db->query($search_query);
	$interest_rate = 1;
	if ($rs) {
		$rs_row = mysqli_fetch_object($rs);

		if ($rs_row) {
			$interest_rate = $rs_row->interest_rate;
		}
	}

	return $interest_rate;
}

function get_current_financial_year() {
    if (date('m') <= 3) {
        return date("Y") - 1;
    } else {
        return date("Y");
    }
}
