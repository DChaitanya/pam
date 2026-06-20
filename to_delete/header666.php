<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script type="text/javascript" src="./media/js/jquery.min.js"></script>
<script type="text/javascript" src="./media/js/ui.datepicker_new.js"></script>
<script type="text/javascript" src="./media/js/fd_cal.js"></script>
<?php
	if (strstr($_SERVER['SCRIPT_NAME'], "monthly_investment.php.orig")) {
		echo '<script type="text/javascript" src="./media/js/highcharts.js"></script>';
	} else if (strstr($_SERVER['SCRIPT_NAME'], "monthly_investment.php") || strstr($_SERVER['SCRIPT_NAME'], "monthly_investment_stock.php")) {
		echo '<script type="text/javascript" src="./media/js/highcharts-7.1.1.js"></script>';
		echo '<script type="text/javascript" src="./media/js/data-7.1.1.js"></script>';
		echo '<script type="text/javascript" src="./media/js/drilldown-7.1.1.js"></script>';
	}
?>
<link type="text/css" rel="stylesheet" href="./media/css/main.css?v1" />
<link rel="stylesheet" type="text/css" media="screen" href="./media/css/flora.datepicker.css">
<title><?php echo $page_title ?> | Personal Accounting Management</title>
</head>
<body>
<div id="header">
    <div id="header-top">
        <div id="menu">
            <ul id="main-nav">
				<li>
					<img class="logo" border="0" alt="PERSONAL ACCOUNTING MANAGEMENT" src="./media/images/logo_im.png" />
				</li>
                <li><a <?php if (strstr($_SERVER['SCRIPT_NAME'], "index.php")) echo 'class="selected" ' ?>href="index.php">FD List</a></li>
                <li><a <?php if (strstr($_SERVER['SCRIPT_NAME'], "on_date_fd_details.php")) echo 'class="selected" ' ?>href="on_date_fd_details.php">On Date FD List</a></li>
                <li><a <?php if (strstr($_SERVER['SCRIPT_NAME'], "namewise_list.php")) echo 'class="selected" ' ?>href="namewise_list.php">Namewise FD List</a></li>
                <li><a <?php if (strstr($_SERVER['SCRIPT_NAME'], "monthwise_list.php")) echo 'class="selected" ' ?>href="monthwise_list.php">Monthwise FD List</a></li>
                <li><a <?php if (strstr($_SERVER['SCRIPT_NAME'], "fy_report.php")) echo 'class="selected" ' ?>href="fy_report.php">FY Report</a></li>
                <li><a <?php if (strstr($_SERVER['SCRIPT_NAME'], "monthly_investment.php")) echo 'class="selected" ' ?>href="monthly_investment.php">Investment Report</a></li>
                <li><a <?php if (strstr($_SERVER['SCRIPT_NAME'], "new_fd.php")) echo 'class="selected" ' ?>href="new_fd.php">New FD</a></li>
            </ul>
        </div>
        <ul id="account_options">
            <li>Welcome <span class="italics-span"><?php echo ucwords(strtolower($_SESSION['firstname']." ".$_SESSION['lastname'])) ?></span>, </li>
            <li><span class="logout"><a href="logout.php">Logout</a></span></li>
        </ul>
    </div>
</div>

<div id="main_content">

<?php
// set the default timezone as 'Asia/Calcutta';
date_default_timezone_set('Asia/Kolkata');
setlocale(LC_MONETARY, "hi_IN.ISCII-DEV");
// $fmt = new NumberFormatter('en_IN', NumberFormatter::CURRENCY);
$fmt = new NumberFormatter('en_IN', NumberFormatter::DECIMAL);
$fmt->setAttribute($fmt::FRACTION_DIGITS, 2); //applies rounding during format
