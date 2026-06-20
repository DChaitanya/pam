<?php
    session_start();
    if (!$_SESSION['is_logged']) {
        header("Location: login.php?redirect=index");
    }

    $page_title = "FD List";
    include("header.php");
    include_once("db_connect.php");
    require_once('ajax.php');

    $search_query = "select a.*, s.scheme_name, u.name from accounts a inner join acc_users u on a.name = u.id inner join deposite_schemes s on a.deposite_scheme = s.id where a.is_active = 1 and s.is_active = 'y' and a.renewal_date <= now()";

    $name = '';
    $deposite_scheme = '';
    $deposited_on = '';
    $matured_on = '';

    $sort_link = '';

    if (isset($_GET['name']) && $_GET['name']) {
        $name = trim($_GET['name']);
        $search_query .= " and a.name = $name";
        $sort_link .= "&name=$name";
    }
    if (isset($_GET['deposite_scheme']) && $_GET['deposite_scheme']) {
        $deposite_scheme = trim($_GET['deposite_scheme']);
        $search_query .= " and deposite_scheme = '$deposite_scheme'";
        $sort_link .= "&deposite_scheme=$deposite_scheme";
    }
    if (isset($_GET['deposited_on']) && $_GET['deposited_on']) {
        $deposited_on = trim($_GET['deposited_on']);
        $search_query .= " and (month(deposite_date) = '$deposited_on' or month(renewal_date) = '$deposited_on')";
        $sort_link .= "&deposited_on=$deposited_on";
    }
    if (isset($_GET['matured_on']) && $_GET['matured_on']) {
        $matured_on = trim($_GET['matured_on']);
        $search_query .= " and month(maturity_date) = '$matured_on'";
        $sort_link .= "&matured_on=$matured_on";
    }

    // $order_by = 'maturity_date';
    $order_by = "";
    $ord = 0;
    if (isset($_GET['ord'])) {
        $ord = $_GET['ord'];

        switch ($ord) {
            case 1:
                $order_by = 'u.name';
                break;
            case 2:
                $order_by = 'deposite_scheme';
                break;
            case 3:
                $order_by = 'period_type, period';
                break;
            case 4:
                $order_by = 'deposite_date';
                break;
            case 5:
                $order_by = 'renewal_date';
                break;
            case 6:
                $order_by = 'rate_of_interest';
                break;
            case 7:
                $order_by = 'deposite_amount';
                break;
            case 8:
                $order_by = 'total_interest';
                break;
            case 9:
                $order_by = 'maturity_amount';
                break;
        }
    }

    $order_type = 'asc';
    $ot = 1;
    $new_ot = 1;
    if (isset($_GET['ot'])) {
        $ot = $_GET['ot'];

        if ($ot == 0) {
            $order_type = 'desc';
            $new_ot = 1;
        } else {
            $new_ot = 0;
        }
    }

    if (!$order_by) {
        $search_query .= " order by maturity_date asc, s.scheme_name asc, u.name asc";
    } else {
        $search_query .= " order by $order_by $order_type";
    }

    $db = new db();
    $rs = $db->query($search_query);
    if ($rs === false) {
        $total_results = false;
    } else {
        $total_results = mysqli_num_rows($rs);
    }

    $user_rs = $db->query("select id, name from acc_users where is_active = 'y' order by name");
    $deposite_schemes_rs = $db->query("select id, scheme_name from deposite_schemes where is_active = 'y' order by scheme_name");

	$locale = (isset($_COOKIE["locale"])) ? $_COOKIE["locale"] : $_SERVER["HTTP_ACCEPT_LANGUAGE"];
	setlocale(LC_ALL, $locale);
?>

<table class="fd-list">
    <?php
        if (isset($_SESSION['msg'])) {
            echo '<tr><td colspan="15" class="msg">'.$_SESSION['msg'].'</td></tr>';
            unset($_SESSION['msg']);
        }
    ?>
    <thead>
    <tr>
        <td colspan="15" align="left">
            <form name="search_frm" id="search_frm" action="" method="GET">
                <strong>Name:</strong>&nbsp;
                <select name="name" id="name" onchange="search_data()">
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
                </select>&nbsp;
                <strong>Deposite Scheme:</strong>&nbsp;
                <select name="deposite_scheme" id="deposite_scheme" onchange="search_data()">
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
                </select>&nbsp;
                <strong>Deposited In:</strong>&nbsp;
                <select name="deposited_on" id="deposited_on" onchange="search_data()">
                    <option value=""></option>
                    <option value="1"<?php if ($deposited_on == 1) echo "selected"?>>January</option>
                    <option value="2"<?php if ($deposited_on == 2) echo "selected"?>>February</option>
                    <option value="3"<?php if ($deposited_on == 3) echo "selected"?>>March</option>
                    <option value="4"<?php if ($deposited_on == 4) echo "selected"?>>April</option>
                    <option value="5"<?php if ($deposited_on == 5) echo "selected"?>>May</option>
                    <option value="6"<?php if ($deposited_on == 6) echo "selected"?>>June</option>
                    <option value="7"<?php if ($deposited_on == 7) echo "selected"?>>July</option>
                    <option value="8"<?php if ($deposited_on == 8) echo "selected"?>>August</option>
                    <option value="9"<?php if ($deposited_on == 9) echo "selected"?>>September</option>
                    <option value="10"<?php if ($deposited_on == 10) echo "selected"?>>October</option>
                    <option value="11"<?php if ($deposited_on == 11) echo "selected"?>>November</option>
                    <option value="12"<?php if ($deposited_on == 12) echo "selected"?>>December</option>
                </select>&nbsp;
                <strong>Matured On:</strong>&nbsp;
                <select name="matured_on" id="matured_on" onchange="search_data()">
                    <option value=""></option>
                    <option value="1"<?php if ($matured_on == 1) echo "selected"?>>January</option>
                    <option value="2"<?php if ($matured_on == 2) echo "selected"?>>February</option>
                    <option value="3"<?php if ($matured_on == 3) echo "selected"?>>March</option>
                    <option value="4"<?php if ($matured_on == 4) echo "selected"?>>April</option>
                    <option value="5"<?php if ($matured_on == 5) echo "selected"?>>May</option>
                    <option value="6"<?php if ($matured_on == 6) echo "selected"?>>June</option>
                    <option value="7"<?php if ($matured_on == 7) echo "selected"?>>July</option>
                    <option value="8"<?php if ($matured_on == 8) echo "selected"?>>August</option>
                    <option value="9"<?php if ($matured_on == 9) echo "selected"?>>September</option>
                    <option value="10"<?php if ($matured_on == 10) echo "selected"?>>October</option>
                    <option value="11"<?php if ($matured_on == 11) echo "selected"?>>November</option>
                    <option value="12"<?php if ($matured_on == 12) echo "selected"?>>December</option>
                </select>
                <input type="hidden" name="ord" value="<?php echo $ord?>" />
                <input type="hidden" name="ot" value="<?php echo $ot?>" />
                <input type="button" name="clear" onclick="clear_search()" value="Clear" />
            </form>
        </td>
    </tr>
    <tr>
        <th rowspan="2" width="50px">#</th>
        <th rowspan="2" width="150px">Acc ID</th>
        <th rowspan="2" width="175px">
            <a href="?<?php echo$sort_link?>&ord=1&ot=<?php echo $ord == 1?$new_ot:$ot?>">Name</a>
            <?php
                if ($ord == 1) {
                    if ($ot == 1) {
                        echo '&nbsp;<img src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th rowspan="2" width="100px">
            <a href="?<?php echo$sort_link?>&ord=2&ot=<?php echo $ord == 2?$new_ot:$ot?>">Deposited In</a>
            <?php
                if ($ord == 2) {
                    if ($ot == 1) {
                        echo '&nbsp;<img src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th rowspan="2" width="75px">
            <a href="?<?php echo$sort_link?>&ord=3&ot=<?php echo $ord == 3?$new_ot:$ot?>">Period</a>
            <?php
                if ($ord == 3) {
                    if ($ot == 1) {
                        echo '&nbsp;<img src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th colspan="3">Dates</th>
        <th rowspan="2" width="60px">
            <a href="?<?php echo$sort_link?>&ord=6&ot=<?php echo $ord == 6?$new_ot:$ot?>">ROI</a>
            <?php
                if ($ord == 6) {
                    if ($ot == 1) {
                        echo '&nbsp;<img src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th colspan="5">Amount</th>
        <th rowspan="2" style="width:125px">Actions</th>
    </tr>
    <tr>
        <th width="95px">
            <a href="?<?php echo$sort_link?>&ord=4&ot=<?php echo $ord == 4?$new_ot:$ot?>">Deposite</a>
            <?php
                if ($ord == 4) {
                    if ($ot == 1) {
                        echo '&nbsp;<img src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th width="95px">
            <a href="?<?php echo$sort_link?>&ord=5&ot=<?php echo $ord == 5?$new_ot:$ot?>">Renewal</a>
            <?php
                if ($ord == 5) {
                    if ($ot == 1) {
                        echo '&nbsp;<img src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th width="95px">
            <a href="?<?php echo$sort_link?>&ord=0&ot=<?php echo $ord == 0?$new_ot:$ot?>">Maturity</a>
            <?php
                if ($ord == 0) {
                    if ($ot == 1) {
                        echo '&nbsp;<img src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th width="90px">
            <a href="?<?php echo$sort_link?>&ord=7&ot=<?php echo $ord == 7?$new_ot:$ot?>">Deposite</a>
            <?php
                if ($ord == 7) {
                    if ($ot == 1) {
                        echo '&nbsp;<img src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th width="90px">
            <a href="?<?php echo$sort_link?>&ord=8&ot=<?php echo $ord == 8?$new_ot:$ot?>">Interest</a>
            <?php
                if ($ord == 8) {
                    if ($ot == 1) {
                        echo '&nbsp;<img src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th width="90px">Cur Int</th>
        <th width="90px">Cur Value</th>
        <th width="90px">
            <a href="?<?php echo$sort_link?>&ord=9&ot=<?php echo $ord == 9?$new_ot:$ot?>">Maturity</a>
            <?php
                if ($ord == 9) {
                    if ($ot == 1) {
                        echo '&nbsp;<img src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
    </tr>
    </thead>
<?php
    $sr_no = 1;
    $total_deposite_amount = 0;
    $total_interest_amount = 0;
    $total_matured_amount = 0;

    $total_cur_interest_amount = 0;
    $total_cur_value = 0;


    if (!$total_results) {
?>
        <tr class="even">
            <th colspan="15">Results are not found.</th>
        </tr>
<?php
    } else {
        while ($row = mysqli_fetch_object($rs)) {
            $total_deposite_amount += $row->deposite_amount;
            $total_interest_amount += $row->total_interest;
            $total_matured_amount += $row->maturity_amount;
            $show_renew_alert = false;

            if (strtotime($row->maturity_date) <= strtotime('now')) {
                $renew_txt = '<a href="renew_fd.php?fdid='.$row->id.'">Renew</a> | ';
                $show_renew_alert = true;
            } else {
                $renew_txt = 'Renew | ';
            }
?>
            <tr class="<?php
                if (isset($_SESSION['fd_id']) && $_SESSION['fd_id'] == $row->id) {
                    echo 'selected';
                    unset($_SESSION['fd_id']);
                } else if ($show_renew_alert) {
                    echo 'renew';
                } else {
                    if ($sr_no % 2) { echo 'odd'; } else { echo 'even'; }
                }
            ?>">
                <td align="right"><?php echo $sr_no++;?></td>
                <td align="left"><?php echo $row->ref_id;?></td>
                <td align="left"><?php echo $row->name;?></td>
                <td align="left"><?php echo $row->scheme_name;?></td>
                <td align="right">
                    <?php echo $row->period?>
                    <?php   if ($row->period_type == 'd') {
                                echo "Days";
                            } elseif ($row->period_type == 'm') {
                                echo "Months";
                            } else {
                                echo "Years";
                            }
                    ?>
                </td>
                <td align="right"><?php echo date("d-m-Y", strtotime($row->deposite_date)) ?></td>
                <td align="right"><?php echo date("d-m-Y", strtotime($row->renewal_date)) ?></td>
                <td align="right"><?php echo date("d-m-Y", strtotime($row->maturity_date)) ?></td>
                <td align="right"><?php echo $fmt->format(round($row->rate_of_interest, 2)) ?> %</td>
                <td align="right"><?php echo $fmt->format($row->deposite_amount) ?></td>
                <td align="right"><?php echo $fmt->format($row->total_interest) ?></td>
                <td align="right">
                    <?php
                        if (strtotime($row->maturity_date) <= strtotime('now')) {
                            // if maturity date is over
                            $int_till_date = $row->total_interest;
                        } else {
                            $int_till_date = new_get_current_interest_value(
                                $row->deposite_amount,
                                $row->renewal_date,
                                $row->maturity_date,
								$row->interest_type,
								$row->deposite_scheme
                            );
                        }
                        echo $fmt->format($int_till_date);
                        $total_cur_interest_amount += $int_till_date;
                    ?>
                </td>
                <td align="right">
                    <?php
                        $cur_value = $row->deposite_amount + $int_till_date;
                        $total_cur_value += $cur_value;
                        echo $fmt->format($cur_value);
                    ?>
                </td>
                <td align="right"><?php echo $fmt->format($row->maturity_amount) ?></td>
                <td align="left">
                    <?php echo $renew_txt ?> <a href="edit_fd.php?fdid=<?php echo $row->id?>">Edit</a> | <a href="delete_fd.php?fdid=<?php echo $row->id ?>">Delete</a>
                </td>
            </tr>
<?php
        }
    }
?>
    <tr>
        <th colspan="9">Total</th>
        <th align="right"><?php echo $fmt->format($total_deposite_amount) ?></th>
        <th align="right"><?php echo $fmt->format($total_interest_amount) ?></th>
        <th align="right"><?php echo $fmt->format($total_cur_interest_amount) ?></th>
        <th align="right"><?php echo $fmt->format($total_cur_value) ?></th>
        <th align="right"><?php echo $fmt->format($total_matured_amount)?></th>
        <th>&nbsp;</th>
    </tr>
</table>

<?php
    include("footer.php");
?>

<script type="text/javascript">
    function search_data() {
        document.forms.search_frm.submit();
    }
    function clear_search() {
        $("#name").val('');
        $("#deposite_scheme").val('');
        $("#deposited_on").val('');
        $("#matured_on").val('');

        document.forms.search_frm.submit();
    }
</script>
