<?php
    require_once __DIR__ . '/auth_guard.php';
    // set the default timezone as 'Asia/Calcutta';
    date_default_timezone_set('Asia/Kolkata');

    include_once("db_connect.php");
    $db = new db();

    $fd_id = isset($_REQUEST["fdid"]) ? (int) $_REQUEST["fdid"] : 0;

    if ($fd_id <= 0) {
        header("Location: index.php");
        exit;
    }

    $ref_id_msg = "";
    $name_msg = "";
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

    // check for POST request
    if (isset($_POST['submit'])) {

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

        $is_valid = true;

        if (!is_not_empty($name)) {
            $name_msg = "Name should be valid.";
            $is_valid = false;
        }
        if (!is_not_empty($deposite_scheme)) {
            $deposite_scheme_msg = "Please select the Deposite Scheme.";
            $is_valid = false;
        }
        if (!is_valid_date($deposite_date)) {
            $deposite_date_msg = "Deposite date should be valid.";
            $is_valid = false;
        }
        if (!is_valid_date($renewal_date)) {
            $deposite_date_msg = "Renewal date should be valid.";
            $is_valid = false;
        }
        if (!is_valid_number($period)) {
            $deposite_period_msg = "Please select the period > 0.";
            $is_valid = false;
        }
        if (!is_not_empty($period_type)) {
            if ($deposite_period_msg) {
                $deposite_period_msg .= "<br/>Please select the period type.";
            } else {
                $deposite_period_msg = "Please select the period type.";
            }
            $is_valid = false;
        }
        if (!is_valid_date($maturity_date)) {
            $maturity_date_msg = "Maturity date should be valid.";
            $is_valid = false;
        }
        if (!is_valid_number($deposite_amount)) {
            $deposite_amount_msg = "Deposite amount should be valid (and > 0).";
            $is_valid = false;
        }
        if (!is_valid_number($rate_of_interest)) {
            $rate_of_interest_msg = "ROI should be valid (and > 0).";
            $is_valid = false;
        }
        if (!is_not_empty($interest_type)) {
            $interest_type_msg = "<span class=\"error\">Please select the Interest type.</span>";
            $is_valid = false;
        }
        if (!is_valid_number($total_interest)) {
            $total_interest_msg = "Total interest should be valid (and > 0).";
            $is_valid = false;
        }
        if (!is_valid_number($maturity_amount)) {
            $maturity_amount_msg = "Maturity amount should be valid (and > 0).";
            $is_valid = false;
        }

        if ($is_valid) {
            $name = ucwords(strtolower($name));
            $update_fd_query = "update accounts set name=?, deposite_scheme=?, deposite_date=?, renewal_date=?, period=?, period_type=?, maturity_date=?, rate_of_interest=?, interest_type=?, deposite_amount=?, total_interest=?, maturity_amount=?, ref_id=? where id = ?";

            $action = 'renewed on ' . date("d-m-Y");

            if ($db->execute($update_fd_query, "sssssssssssssi", [$name, $deposite_scheme, $deposite_date, $renewal_date, $period, $period_type, $maturity_date, $rate_of_interest, $interest_type, $deposite_amount, $total_interest, $maturity_amount, $ref_id, $fd_id])) {
                $acc_history = $_SESSION['acc_history'];
                $insert_into_acc_history = "insert into accounts_history values(null, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, null)";

                $db->execute($insert_into_acc_history, "issssssssssssss", [$fd_id, $ref_id, $acc_history['name'], $acc_history['deposite_scheme'], $acc_history['deposite_date'], $acc_history['renewal_date'], $acc_history['period'], $acc_history['period_type'], $acc_history['maturity_date'], $acc_history['rate_of_interest'], $acc_history['interest_type'], $acc_history['deposite_amount'], $acc_history['total_interest'], $acc_history['maturity_amount'], $action]);

                $_SESSION['msg'] = "Accounts renewed successfully";
                $_SESSION['fd_id'] = $fd_id;
                //exit(0);
                header("Location: ./index.php");
                exit;
            } else {
                echo "Error while updating FD details.";
            }
        }
    } else {
        $fd_rs = $db->select("select * from accounts where id = ? and `maturity_date` <= curdate()", "i", [$fd_id]);

        if (!$fd_rs || !mysqli_num_rows($fd_rs)) {
            header("Location: index.php");
            exit;
        }
        include_once('ajax.php');

        $fd_rec = mysqli_fetch_object($fd_rs);

        // initialize variables
        $ref_id = $fd_rec->ref_id;
        $name = $fd_rec->name;
        $deposite_scheme = $fd_rec->deposite_scheme;
        $deposite_date = $fd_rec->deposite_date;
        $renewal_date = $fd_rec->maturity_date;
        $period = $fd_rec->period;
        $period_type = $fd_rec->period_type;
        $maturity_date = get_maturity_date($renewal_date, $period, $period_type);
        $deposite_amount = $fd_rec->maturity_amount;
        $rate_of_interest = $fd_rec->rate_of_interest;
        $interest_type = $fd_rec->interest_type;
        $deposite_type = 0;
        $total_interest = get_total_interest($deposite_amount, $rate_of_interest, $period, $period_type, $deposite_type, $interest_type);
        $maturity_amount = $deposite_amount + $total_interest;

        $acc_history = array (
        	'ref_id' => $ref_id,
            'name' => $name,
            'deposite_scheme' => $deposite_scheme,
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

    $page_title = "Renew FD";
    include("header.php");
    $user_rs = $db->query("select id, name from acc_users where is_active = 'y'");
    $deposite_schemes_rs = $db->query("select id, scheme_name from deposite_schemes where is_active = 'y'");
?>

<form action="" method="post">
<table class="update_fd">
    <tr><th colspan="2">Renew FD</th></tr>
    <tr>
        <th>Account ID:</th>
        <td><input type="text" name="ref_id" id="ref_id" value="<?php echo $ref_id?>" style="width:160px" /></td>
        <td><?php echo $ref_id_msg ?></td>
    </tr>
    <tr class="odd">
        <td>Name:</td>
        <td>
            <select name="name" id="name" style="width:160px">
                <option value=""></option>
                <?php
                echo "***" . $name;
                while ($user_row = mysqli_fetch_object($user_rs)) {
                    if ($name == $user_row->id) {
                        echo "<option value=\"$user_row->id\" selected>$user_row->name</option>";
                    } else {
                        echo "<option value=\"$user_row->id\">$user_row->name</option>";
                    }
                }
                ?>
            </select>
            <?php echo $name_msg?>
        </td>
    </tr>
    <tr class="even">
        <td>Deposite Scheme:</td>
        <td>
            <select name="deposite_scheme" id="deposite_scheme">
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
            <?php echo $deposite_scheme_msg?>
        </td>
    </tr>
    <tr class="odd">
        <td>Deposite Date:</td>
        <td>
            <input type="text" name="deposite_date" id="deposite_date" value="<?php echo $deposite_date?>" readonly="readonly" />
            <?php echo $deposite_date_msg?>
        </td>
    </tr>
    <tr class="even">
        <td>Renewal Date:</td>
        <td>
            <input type="text" name="renewal_date" id="renewal_date" value="<?php echo $renewal_date?>" readonly="readonly" />
            <?php echo $renewal_date_msg?>
        </td>
    </tr>
    <tr class="odd">
        <td>Period:</td>
        <td>
            <input type="text" name="period" id="period" value="<?php echo $period?>" />
            <select name="period_type" id="period_type">
                <option value=""></option>
                <option value="d"<?php if ($period_type == "d") echo "selected"?>>Days</option>
                <option value="m"<?php if ($period_type == "m") echo "selected"?>>Months</option>
                <option value="y"<?php if ($period_type == "y") echo "selected"?>>Years</option>
            </select>
            <?php echo $deposite_period_msg?>
        </td>
    </tr>
    <tr class="even">
        <td>Maturity Date:</td>
        <td>
            <input type="text" name="maturity_date" id="maturity_date" value="<?php echo $maturity_date?>" readonly="readonly" />
            <?php echo $maturity_date_msg?>
        </td>
    </tr>
    <tr class="odd">
        <td>Deposite Amount:</td>
        <td>
            <input type="text" name="deposite_amount" id="deposite_amount" value="<?php echo $deposite_amount?>" readonly="readonly" />
            <?php echo $deposite_amount_msg?>
        </td>
    </tr>
    <tr class="even">
        <td>Rate of Interest:</td>
        <td>
            <input type="text" name="rate_of_interest" id="rate_of_interest" value="<?php echo $rate_of_interest?>" /> %
            <?php echo $rate_of_interest_msg?>
        </td>
    </tr>
    <tr class="even">
        <td>Interest Type:</td>
        <td><select name="interest_type" id="interest_type">
            <option value=""></option>
            <option value="0" <?php if ($interest_type == "0") echo "selected"?>>Simple Interest</option>
            <option value="4" <?php if ($interest_type == "4") echo "selected"?>>Compound Interest (Queartly)</option>
            <option value="2" <?php if ($interest_type == "2") echo "selected"?>>Compound Interest (Half Yearly)</option>
            <option value="1" <?php if ($interest_type == "1") echo "selected"?>>Compound Interest (Yearly)</option>
            </select>
        <?php echo $interest_type_msg?></td>
    </tr>
    <tr class="odd">
        <td>Total Interest:</td>
        <td>
            <input type="text" name="total_interest" id="total_interest" value="<?php echo $total_interest?>" />
            <?php echo $total_interest_msg?>
        </td>
    </tr>
    <tr class="even">
        <td>Maturity Amount:</td>
        <td>
            <input type="text" name="maturity_amount" id="maturity_amount" value="<?php echo $maturity_amount?>" />
            <?php echo $maturity_amount_msg?>
        </td>
    </tr>
    <tr>
        <th colspan="2"><input type="submit" name="submit" id="submit" value="Renew Fix Deposite" /></th>
    </tr>
</table>
</form>

<?php
    include("footer.php");
?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#maturity_date').datepicker({
            dateFormat: 'yy-mm-dd',
            buttonImage: './media/images/calendar.gif',
            buttonImageOnly: true,
            showOn: 'button'
        });

        $('#period').change(cal_maturity_date1);
        $('#period_type').change(cal_maturity_date1);

        $('#period').change(cal_maturity_amount);
        $('#period_type').change(cal_maturity_amount);
        $('#rate_of_interest').change(cal_maturity_amount);
        $('#interest_type').change(cal_maturity_amount);
    });
</script>
