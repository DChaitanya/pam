<?php

session_start();
if (!$_SESSION['is_logged']) {
    header("Location: login.php?redirect=delete_fd");
}
// set the default timezone as 'Asia/Calcutta';
date_default_timezone_set('Asia/Kolkata');


include_once("db_connect.php");

$db = new db();

$fd_id = $_REQUEST["fdid"];

if (!is_numeric($fd_id)) {
    header("Location: index.php");
}

$name_msg = "";
$ref_id_msg = "";
$deposite_scheme_msg = "";
$deposite_date_msg = "";
$renewal_date_msg = "";
$deposite_period_msg = "";
$maturity_date_msg = "";
$deposite_amount_msg = "";
$rate_of_interest_msg = "";
$interest_type_msg = "";
$total_interest_msg = "";
$maturity_amount_msg = "";
$total_interest_till_date_msg = "";
$total_amount_till_date_msg = "";

$db = new db();

// check for POST request
if (isset($_POST['submit'])) {
    $fd_id = $_GET['fdid'];
    require_once("validation.php");

    // assign post values
    $ref_id = trim($_POST['ref_id']);
    $name = trim($_POST['name']);
    $deposite_scheme = trim($_POST['deposite_scheme']);
    $deposite_date = trim($_POST['deposite_date']);
    $renewal_date = trim($_POST['renewal_date']);
    $period = trim($_POST['period']);
    $period_type = trim($_POST['period_type']);
    $maturity_date = trim($_POST['maturity_date']);
    $deposite_amount = trim($_POST['deposite_amount']);
    $rate_of_interest = trim($_POST['rate_of_interest']);
	$interest_type = trim($_POST['interest_type']);
    $total_interest = trim($_POST['total_interest']);
    $maturity_amount = trim($_POST['maturity_amount']);
    $total_interest_till_date = trim($_POST['total_interest_till_date']);
    $total_amount_till_date = trim($_POST['total_amount_till_date']);

    $is_valid = true;

    if (!is_number($total_interest_till_date)) {
        $total_interest_till_date_msg = "Total interest till date should be valid .";
        $is_valid = false;
    }
    if (!is_valid_number($total_amount_till_date)) {
        $total_amount_till_date_msg = "Total Maturity amount till date should be valid (and > 0).";
        $is_valid = false;
    }

    if ($is_valid) {
        $total_interest = $total_interest_till_date;
        $maturity_amount = $total_amount_till_date;

        if (strtotime($maturity_date) > strtotime('now')) {
            $action = 'Prematured closed on ' . date("d-m-Y");
        } else {
            $action = 'FD closed on ' . date("d-m-Y");
        }

        $closed_date = date("Y-m-d");

        $delete_query = "delete from accounts where id = '$fd_id'";
        $rs = $db->query($delete_query);
        $acc_history = $_SESSION['acc_history'];
        $insert_into_acc_history = "insert into accounts_history values(null, '$fd_id', '$ref_id', '$acc_history[name]', '$acc_history[deposite_scheme]', '$acc_history[deposite_date]', '$acc_history[renewal_date]', '$acc_history[period]', '$acc_history[period_type]', '$acc_history[maturity_date]', '$acc_history[rate_of_interest]', '$acc_history[interest_type]', '$acc_history[deposite_amount]', '$total_interest', '$maturity_amount', '$action', '$closed_date')";
        unset($_SESSION['acc_history']);
        $db->query($insert_into_acc_history);

        $_SESSION['msg'] = "FD Account is deleted successfully";
        header("Location: index.php");
    }
} else {
    $get_fd_data = "select * from accounts where id = '$fd_id'";

    $fd_rs = $db->query($get_fd_data);

    if (!mysqli_num_rows($fd_rs)) {
        header("Location: index.php");
    }
    include_once('ajax.php');

    $fd_rec = mysqli_fetch_object($fd_rs);

    // initialize variables
    $ref_id = $fd_rec->ref_id;
    $name = $fd_rec->name;
    $deposite_scheme = $fd_rec->deposite_scheme;
    $deposite_date = $fd_rec->deposite_date;
    $renewal_date = $fd_rec->renewal_date;
    $period = $fd_rec->period;
    $period_type = $fd_rec->period_type;
    $maturity_date = $fd_rec->maturity_date;
    $deposite_amount = $fd_rec->deposite_amount;
    $rate_of_interest = $fd_rec->rate_of_interest;
    $interest_type = $fd_rec->interest_type;
    $total_interest = $fd_rec->total_interest;
    $maturity_amount = $deposite_amount + $total_interest;

    $total_interest_till_date = 0;
    if (strtotime($maturity_date) <= strtotime('now')) {
        // if maturity date is over
        $total_interest_till_date = $total_interest;
    } else {
        $total_interest_till_date = get_current_interest_value(
            $deposite_amount,
            $renewal_date,
            $maturity_date
        );
    }
    $total_amount_till_date = $deposite_amount + $total_interest_till_date;

    $acc_history = array (
        'name' => $fd_rec->name,
        'deposite_scheme' => $fd_rec->deposite_scheme,
        'deposite_date' => $deposite_date,
        'renewal_date' =>  $fd_rec->renewal_date,
        'period' => $period,
        'period_type' => $period_type,
        'maturity_date' =>  $fd_rec->maturity_date,
        'deposite_amount' => $fd_rec->deposite_amount,
        'rate_of_interest' => $fd_rec->rate_of_interest,
        'interest_type' => $fd_rec->interest_type,
        'total_interest' => $fd_rec->total_interest,
        'maturity_amount' => $fd_rec->maturity_amount,
    );

    // add values in session
    $_SESSION['acc_history'] = $acc_history;
}

$page_title = "Delete FD";
include("header.php");
$user_rs = $db->query("select id, name from acc_users where is_active = 'y'");
$deposite_schemes_rs = $db->query("select id, scheme_name from deposite_schemes where is_active = 'y'");
?>

<form action="" method="post">
<h1 align="center">Delete FD</h1>
<table class="delete_fd">
    <tr >
        <th>Account ID:</th>
        <td><input type="text" name="ref_id" id="ref_id" value="<?php echo $ref_id?>" style="width:160px" /></td>
        <td><?php echo $ref_id_msg ?></td>
    </tr>
    <tr >
        <th width="120px">Name:</th>
        <td width="180px">
            <select name="name" id="name" >
                <?php
                while ($user_row = mysqli_fetch_object($user_rs)) {
                    if ($name == $user_row->id) {
                        echo "<option value=\"$user_row->id\" selected>$user_row->name</option>";
                        break;
                    }
                }
                ?>
            </select>
        </td>
        <td width="250px"><?php echo $name_msg?></td>
    </tr>
    <tr>
        <th>Deposite Scheme:</th>
        <td>
            <select name="deposite_scheme" id="deposite_scheme" style="width:160px" >
                <?php
                    while ($ds_row = mysqli_fetch_object($deposite_schemes_rs)) {
                        if ($deposite_scheme == $ds_row->id) {
                            echo "<option value=\"$ds_row->id\" selected>$ds_row->scheme_name</option>";
                            break;
                        }
                    }
                ?>
            </select>
        </td>
        <td><?php echo $deposite_scheme_msg?></td>
    </tr>
    <tr >
        <th>Deposite Date:</th>
        <td><input type="text" name="deposite_date" id="deposite_date" value="<?php echo $deposite_date?>" readonly="readonly" style="width:140px" /></td>
        <td><?php echo $deposite_date_msg?></td>
    </tr>
    <tr>
        <th>Renewal Date:</th>
        <td><input type="text" name="renewal_date" id="renewal_date" value="<?php echo $renewal_date?>" readonly="readonly" style="width:140px" /></td>
        <td><?php echo $renewal_date_msg?></td>
    </tr>
    <tr >
        <th>Period:</th>
        <td>
            <input type="text" name="period" id="period" value="<?php echo $period?>" style="width:75px" readonly="readonly" />
            <select name="period_type" id="period_type" style="width:80px" >
                <?php
                    if ($period_type == "d") {
                        echo '<option value="d" selected>Days</option>';
                    } elseif ($period_type == "m") {
                        echo '<option value="m" selected>Months</option>';
                    } elseif ($period_type == "y") {
                        echo '<option value="y" selected>Years</option>';
                    }
                ?>
            </select>
        </td>
        <td><?php echo $deposite_period_msg?></td>
    </tr>
    <tr>
        <th>Maturity Date:</th>
        <td><input type="text" name="maturity_date" id="maturity_date" value="<?php echo $maturity_date?>" readonly="readonly" style="width:140px" /></td>
        <td><?php echo $maturity_date_msg?></td>
    </tr>
    <tr >
        <th>Deposite Amount:</th>
        <td><input type="text" name="deposite_amount" id="deposite_amount" value="<?php echo $deposite_amount?>" style="width:160px" readonly="readonly" /></td>
        <td><?php echo $deposite_amount_msg?></td>
    </tr>
    <tr>
        <th>Rate of Interest:</th>
        <td><input type="text" name="rate_of_interest" id="rate_of_interest" value="<?php echo $rate_of_interest?>" style="width:150px" readonly="readonly" /> %</td>
        <td><?php echo $rate_of_interest_msg?></td>
    </tr>
    <tr >
        <th>Total Interest:</th>
        <td><input type="text" name="total_interest" id="total_interest" value="<?php echo $total_interest?>" style="width:160px" readonly="readonly" /></td>
        <td><?php echo $total_interest_msg?></td>
    </tr>
    <tr>
        <th>Maturity Amount:</th>
        <td><input type="text" name="maturity_amount" id="maturity_amount" value="<?php echo $maturity_amount?>" style="width:160px" readonly="readonly" /></td>
        <td><?php echo $maturity_amount_msg?></td>
    </tr>
    <tr >
        <th>Total Interest till Date:</th>
        <td>
            <input type="text" name="total_interest_till_date" id="total_interest_till_date" value="<?php echo $total_interest_till_date?>" style="width:160px" />
        </td>
        <td><?php echo $total_interest_till_date_msg?></td>
    </tr>
    <tr>
        <th>Total Amount till Date:</th>
        <td>
            <input type="text" name="total_amount_till_date" id="total_amount_till_date" value="<?php echo $total_amount_till_date?>" style="width:160px" />
        </td>
        <td><?php echo $total_amount_till_date_msg?></td>
    </tr>
    <tr >
        <td>&nbsp;</td>
        <td><input type="submit" name="submit" id="submit" value="Delete Fix Deposite" onclick="return confirm('Are you sure to delete this FD account?')" /></td>
        <td>&nbsp;</td>
    </tr>
</table>
</form>

<?php
    include("footer.php");
?>

<script type="text/javascript">
    $(document).ready(function() {
        /*
        $('#deposite_date').datepicker({
            dateFormat: 'yy-mm-dd',
            buttonImage: './media/images/calendar.gif',
            buttonImageOnly: true,
            showOn: 'button'
        });
        $('#renewal_date').datepicker({
            dateFormat: 'yy-mm-dd',
            buttonImage: './media/images/calendar.gif',
            buttonImageOnly: true,
            showOn: 'button'
        });
        $('#maturity_date').datepicker({
            dateFormat: 'yy-mm-dd',
            buttonImage: './media/images/calendar.gif',
            buttonImageOnly: true,
            showOn: 'button'
        });
        */

        $('#period').change(cal_maturity_date1);
        $('#period_type').change(cal_maturity_date1);
        $('#renewal_date').change(cal_maturity_date1);

        $('#period').change(cal_maturity_amount);
        $('#period_type').change(cal_maturity_amount);
        $('#deposite_amount').change(cal_maturity_amount);
        $('#rate_of_interest').change(cal_maturity_amount);
        $('#interest_type').change(cal_maturity_amount);
    });
</script>
