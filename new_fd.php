<?php
    require_once __DIR__ . '/auth_guard.php';

    include_once("db_connect.php");

    function calculateMonthlyPayout($start_date, $end_date, $deposite_amount, $interest_rate) {
        $interval = $start_date->diff($end_date)->days;
        $interest_rate = $interest_rate / 100;
        $monthly_interest = $interest_rate / 12;
        $days_in_month = $start_date->format('t');
        $interest = $deposite_amount * $monthly_interest * ($interval/$days_in_month);

        $payouts = array($start_date->format('Y-m-d'), $end_date->format('Y-m-d'), $interest);

        return $payouts;
    }

    function calculateQueartlyPayout($start_date, $end_date, $deposite_amount, $interest_rate) {
        $interval = $start_date->diff($end_date)->days;
        $interest_rate = $interest_rate / 100;
        $queartly_interest = $interest_rate / 4;
        $days_in_quearter = 91.25;
        $interest = $deposite_amount * $queartly_interest * ($interval/$days_in_quearter);

        $payouts = array($start_date->format('Y-m-d'), $end_date->format('Y-m-d'), $interest);

        return $payouts;
    }

    // Parameterized INSERT into accounts (prevents SQL injection, S1).
    function add_account($db, $name, $deposite_scheme, $deposite_date, $renewal_date, $period, $period_type, $maturity_date, $rate_of_interest, $interest_type, $deposite_amount, $total_interest, $maturity_amount, $is_active, $ref_id) {
        $sql = "insert into accounts (name, deposite_scheme, deposite_date, renewal_date, period, period_type, maturity_date, rate_of_interest, interest_type, deposite_amount, total_interest, maturity_amount, is_active, ref_id) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $res = $db->execute($sql, str_repeat('s', 14), [$name, $deposite_scheme, $deposite_date, $renewal_date, $period, $period_type, $maturity_date, $rate_of_interest, $interest_type, $deposite_amount, $total_interest, $maturity_amount, $is_active, $ref_id]);
        return $res ? $res['insert_id'] : false;
    }

    $db = new db();

    // initialize variables
    $name = '';
    $account_id = '';
    $deposite_scheme = '';
    $deposite_date = '';
    $period = '';
    $period_type = '';
    $maturity_date = '';
    $deposite_amount = '';
    $deposite_type = '';
    $rate_of_interest = '';
    $total_interest = '';
    $interest_type = '';
    $maturity_amount = '';
    $is_active = 0;

    $name_msg = "";
    $account_id_msg = "";
    $deposite_scheme_msg = "";
    $deposite_date_msg = "";
    $deposite_period_msg = "";
    $maturity_date_msg = "";
    $deposite_amount_msg = "";
    $deposite_type_msg = "";
    $rate_of_interest_msg = "";
    $interest_type_msg = "";
    $total_interest_msg = "";
    $maturity_amount_msg = "";

    // check for POST request
    if (isset($_POST['submit'])) {

        require_once("validation.php");

        // assign post values
        $name = trim($_POST['name']);
        $account_id = trim($_POST['account_id']);
        $deposite_scheme = trim($_POST['deposite_scheme']);
        $deposite_date = trim($_POST['deposite_date']);
        $period = trim($_POST['period']);
        $period_type = trim($_POST['period_type']);
        $maturity_date = trim($_POST['maturity_date']);
        $deposite_amount = trim($_POST['deposite_amount']);
        $deposite_type = trim($_POST['deposite_type']);
        $rate_of_interest = trim($_POST['rate_of_interest']);
        $interest_type = trim($_POST['interest_type']);
        $total_interest = trim($_POST['total_interest']);
        $maturity_amount = trim($_POST['maturity_amount']);

        $is_valid = true;

        if (!is_not_empty($name)) {
            $name_msg = "<span class=\"error\">Please select name.</span>";
            $is_valid = false;
        }
        if (!is_not_empty($account_id)) {
        	$account_id_msg = "<span class=\"error\">Please select account id. Use 01 for new/temporary account id</span>";
        	$is_valid = false;
        }
        if (!is_not_empty($deposite_scheme)) {
            $deposite_scheme_msg = "<span class=\"error\">Please select Deposite Scheme.</span>";
            $is_valid = false;
        }
        if (!is_valid_date($deposite_date)) {
            $deposite_date_msg = "<span class=\"error\">Deposite date should be valid.</span>";
            $is_valid = false;
        }
        if (!is_valid_number($period)) {
            $deposite_period_msg = "<span class=\"error\">Please select period > 0.</span>";
            $is_valid = false;
        }
        if (!is_not_empty($period_type)) {
            if ($deposite_period_msg) {
                $deposite_period_msg .= "<span class=\"error\"> Please select period type.</span>";
            } else {
                $deposite_period_msg = "<span class=\"error\">Please select period type.</span>";
            }
            $is_valid = false;
        }
        if (!is_valid_date($maturity_date)) {
            $maturity_date_msg = "<span class=\"error\">Maturity date should be valid.</span>";
            $is_valid = false;
        }
        if (!is_valid_number($deposite_amount)) {
            $deposite_amount_msg = "<span class=\"error\">Deposite amount should be valid (and > 0).</span>";
            $is_valid = false;
        }
        if (!is_not_empty($deposite_type)) {
            $deposite_type_msg = "<span class=\"error\">Please select Deposite type.</span>";
            $is_valid = false;
        }
        if (!is_valid_number($rate_of_interest)) {
            $rate_of_interest_msg = "<span class=\"error\">ROI should be valid (and > 0).</span>";
            $is_valid = false;
        }
        if (!is_not_empty($interest_type)) {
            $interest_type_msg = "<span class=\"error\">Please select Interest type.</span>";
            $is_valid = false;
        }
        if (!is_valid_number($total_interest)) {
            $total_interest_msg = "<span class=\"error\">Total interest should be valid (and > 0).</span>";
            $is_valid = false;
        }
        if (!is_valid_number($maturity_amount)) {
            $maturity_amount_msg = "<span class=\"error\">Maturity amount should be valid (and > 0).</span>";
            $is_valid = false;
        }

        if ($is_valid) {
            $is_active = 1;

            if ($deposite_type == 1 or $deposite_type == 2 or $deposite_type == 3) {
                // Create FD interest payout rows for each quarter
                // 1. Main row for create date, close date, amount and maturity amount, inital amount = maturity amount
                // 2. Calculate interet for every quarter and add separare row
                $name = ucwords(strtolower($name));
                $fs_id = add_account($db, $name, $deposite_scheme, $deposite_date, $deposite_date, $period, $period_type, $maturity_date, $rate_of_interest, $interest_type, $deposite_amount, 0, $deposite_amount, $is_active, "$account_id-PRI");

                if ($deposite_type == 1) {
                    $interval = new DateInterval('P1M');
                    $end = new DateTime($maturity_date);

                    $renewal_date = new DateTime($deposite_date);
                    $payout_part = 1;
                    while (True) {
                        $date1 = clone $renewal_date;
                        $date2 = $renewal_date->add($interval);
                        // echo $end >= $date2;
                        if ($end < $date2) {
                            break;
                        }
                        $payout = calculateMonthlyPayout($date1, $date2, $deposite_amount, $rate_of_interest);
                        $fs_id = add_account($db, $name, $deposite_scheme, $deposite_date, $payout[0], $period, $period_type, $payout[1], $rate_of_interest, $interest_type, 0, $payout[2], $payout[2], $is_active, "$account_id-M$payout_part");
                        $payout_part++;
                    }

                    if ($end != $renewal_date and $end != $date1) {
                        $payout = calculateMonthlyPayout($date1, $end, $deposite_amount, $rate_of_interest);

                        $fs_id = add_account($db, $name, $deposite_scheme, $deposite_date, $payout[0], $period, $period_type, $payout[1], $rate_of_interest, $interest_type, 0, $payout[2], $payout[2], $is_active, "$account_id-M$payout_part");

                    }
                } else if ($deposite_type == 3) {
                    $interval = new DateInterval('P1M');
                    $end = new DateTime($maturity_date);

                    $renewal_date = new DateTime($deposite_date);
                    $payout_part = 1;
                    while (True) {
                        $date1 = clone $renewal_date;
                        $date2 = $renewal_date->add($interval);
                        // replace $date2 day as 1st
                        $date2->setDate($date2->format('Y'), $date2->format('m'), 1);

                        // minus 1 day
                        $date2->sub(new DateInterval('P1D'));

                        if ($end < $date2) {
                            break;
                        }
                        $payout = calculateMonthlyPayout($date1, $date2, $deposite_amount, $rate_of_interest);
                        $fs_id = add_account($db, $name, $deposite_scheme, $deposite_date, $payout[0], $period, $period_type, $payout[1], $rate_of_interest, $interest_type, 0, $payout[2], $payout[2], $is_active, "$account_id-M$payout_part");
                        $payout_part++;
                    }

                    if ($end != $renewal_date and $end != $date1) {
                        $payout = calculateMonthlyPayout($date1, $end, $deposite_amount, $rate_of_interest);

                        $fs_id = add_account($db, $name, $deposite_scheme, $deposite_date, $payout[0], $period, $period_type, $payout[1], $rate_of_interest, $interest_type, 0, $payout[2], $payout[2], $is_active, "$account_id-M$payout_part");

                    }
                } else {
                    $interval = new DateInterval('P3M');
                    $end = new DateTime($maturity_date);

                    $renewal_date = new DateTime($deposite_date);
                    $payout_part = 1;
                    while (True) {
                        $date1 = clone $renewal_date;
                        $date2 = $renewal_date->add($interval);
                        if ($end < $date2) {
                            break;
                        }
                        $payout = calculateQueartlyPayout($date1, $date2, $deposite_amount, $rate_of_interest);
                        $fs_id = add_account($db, $name, $deposite_scheme, $deposite_date, $payout[0], $period, $period_type, $payout[1], $rate_of_interest, $interest_type, 0, $payout[2], $payout[2], $is_active, "$account_id-Q$payout_part");
                        $payout_part++;
                    }

                    if ($end != $renewal_date and $end != $date1) {
                        $payout = calculateQueartlyPayout($date1, $end, $deposite_amount, $rate_of_interest);

                        $fs_id = add_account($db, $name, $deposite_scheme, $deposite_date, $payout[0], $period, $period_type, $payout[1], $rate_of_interest, $interest_type, 0, $payout[2], $payout[2], $is_active, "$account_id-Q$payout_part");
                    }
                }
            } else {
                $name = ucwords(strtolower($name));
                $fs_id = add_account($db, $name, $deposite_scheme, $deposite_date, $deposite_date, $period, $period_type, $maturity_date, $rate_of_interest, $interest_type, $deposite_amount, $total_interest, $maturity_amount, $is_active, "$account_id");
            }

			if ($fs_id) {
                $_SESSION['fd_id'] = $fs_id;
                $_SESSION['msg'] = "New FD Account is added successfully";
                header("Location: ./index.php");
            } else {
                echo "Error while adding new FD data.";
            }
        }
    }

    $page_title = "New FD";
    include("header.php");
    $user_rs = $db->query("select id, name from acc_users where is_active = 'y' order by name");
    $deposite_schemes_rs = $db->query("select id, scheme_name from deposite_schemes where is_active = 'y' order by scheme_name");
?>

<form action="" method="post">
<table class="add_fd">
    <tr><th colspan="2">Add New FD</th></tr>
    <tr >
        <td width="160px">Name:</td>
        <td width="180px">
            <select name="name" id="name">
                <option value=""></option>
                <?php
                while ($user_row = mysqli_fetch_object($user_rs)) {
                    if ($name == $user_row->id) {
                        echo "<option value=\"$user_row->id\" selected>$user_row->name</option>";
                    } else {
                        echo "<option value=\"$user_row->id\">$user_row->name</option>";
                    }
                }
                ?>
            </select>
        <?php echo $name_msg?></td>
    </tr>
    <tr>
    	<td class="td-title">Account ID:</td>
    	<td class="td-input"><input type="text" name="account_id" id="account_id" value="<?php echo $account_id ?>" /><?php echo $account_id_msg ?></td>
    </tr>
    <tr >
        <td>Deposite Scheme:</td>
        <td>
            <select name="deposite_scheme" id="deposite_scheme" >
                <option value=""></option>
                <?php
                    while ($ds_row = mysqli_fetch_object($deposite_schemes_rs)) {
                        if ($deposite_scheme == $ds_row->id) {
                            echo "<option value=\"$ds_row->id\" selected>$ds_row->scheme_name</option>";
                        } else {
                            echo "<option value=\"$ds_row->id\">$ds_row->scheme_name</option>";
                        }
                    }
                ?>
            </select>
        <?php echo $deposite_scheme_msg?></td>
    </tr>
    <tr >
        <td>Deposite Date:</td>
        <td><input type="text" name="deposite_date" id="deposite_date" value="<?php echo $deposite_date?>" readonly="readonly" /><?php echo $deposite_date_msg?></td>
    </tr>
    <tr >
        <td>Period:</td>
        <td>
            <input type="text" name="period" id="period" value="<?php echo $period?>" style="width:90px" />
            <select name="period_type" id="period_type" style="width:85px">
                <option value=""></option>
                <option value="d"<?php if ($period_type == "d") echo "selected"?>>Days</option>
                <option value="m"<?php if ($period_type == "m") echo "selected"?>>Months</option>
                <option value="y"<?php if ($period_type == "y") echo "selected"?>>Years</option>
            </select>
        <?php echo $deposite_period_msg ?></td>
    </tr>
    <tr >
        <td>Maturity Date:</td>
        <td><input type="text" name="maturity_date" id="maturity_date" value="<?php echo $maturity_date?>" readonly="readonly" />
        <?php echo $maturity_date_msg ?></td>
    </tr>
    <tr >
        <td>Deposite Amount:</td>
        <td><input type="text" name="deposite_amount" id="deposite_amount" value="<?php echo $deposite_amount?>" /> <strong>Rs.</strong>
        <?php echo $deposite_amount_msg ?></td>
    </tr>
    <tr >
        <td>Deposite Type:</td>
        <td>
            <select name="deposite_type" id="deposite_type">
                <option value=""></option>
                <option value="0" <?php if ($deposite_type == "0") echo "selected"?>>Cumulative</option>
                <option value="1" <?php if ($deposite_type == "1") echo "selected"?>>Interest Payout Monthly</option>
                <option value="1" <?php if ($deposite_type == "3") echo "selected"?>>Interest Payout Monthly (on Month End)</option>
                <option value="2" <?php if ($deposite_type == "2") echo "selected"?>>Interest Payout Queartly</option>
            </select>
            <?php echo $deposite_type_msg ?></td>
            </td>
    </tr>
    <tr >
        <td>Rate of Interest:</td>
        <td><input type="text" name="rate_of_interest" id="rate_of_interest" value="<?php echo $rate_of_interest?>" />&nbsp;&nbsp;<strong>%</strong>
        <?php echo $rate_of_interest_msg ?></td>
    </tr>
    <tr >
        <td>Interest Type:</td>
        <td><select name="interest_type" id="interest_type">
            <option value=""></option>
            <option value="0" <?php if ($interest_type == "0") echo "selected"?>>Simple Interest</option>
            <option value="4" <?php if ($interest_type == "4") echo "selected"?>>Compound Interest (Queartly)</option>
            <option value="2" <?php if ($interest_type == "2") echo "selected"?>>Compound Interest (Half Yearly)</option>
            <option value="1" <?php if ($interest_type == "1") echo "selected"?>>Compound Interest (Yearly)</option>
            </select>
        <?php echo $interest_type_msg ?></td>
    </tr>
    <tr >
        <td>Total Interest:</td>
        <td><input type="text" name="total_interest" id="total_interest" value="<?php echo $total_interest?>" /> <strong>Rs.</strong>
        <?php echo $total_interest_msg ?></td>
    </tr>
    <tr >
        <td>Maturity Amount:</td>
        <td><input type="text" name="maturity_amount" id="maturity_amount" value="<?php echo $maturity_amount?>" /> <strong>Rs.</strong>
        <?php echo $maturity_amount_msg ?></td>
    </tr>
    <tr>
        <th colspan="2">
        	<input type="submit" name="submit" id="submit" value="Add Fix Deposite" />
        	<input type="reset" name="reset" id="reset" value="Clear" />
        </th>
    </tr>
</table>
</form>

<?php
    include("footer.php");
?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#deposite_date').datepicker({
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

        $('#period').change(cal_maturity_date);
        $('#period_type').change(cal_maturity_date);
        $('#deposite_date').change(cal_maturity_date);

        $('#period').change(cal_maturity_amount);
        $('#period_type').change(cal_maturity_amount);
        $('#deposite_amount').change(cal_maturity_amount);
        $('#deposite_type').change(cal_maturity_amount);
        $('#rate_of_interest').change(cal_maturity_amount);
        $('#interest_type').change(cal_maturity_amount);
    });
</script>
