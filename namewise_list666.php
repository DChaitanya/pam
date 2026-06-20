<?php
    session_start();
    if (!$_SESSION['is_logged']) {
        header("Location: login.php?redirect=namewise_list");
    }

    $page_title = "Namewise FD List";
    include("header.php");
    include_once("db_connect.php");
    require_once('ajax.php');

    $search_query = "select a.*, s.scheme_name, a.name as name_id, u.name from accounts a inner join acc_users u on u.id = a.name inner join deposite_schemes s on a.deposite_scheme = s.id where a.is_active = 1 and s.is_active = 'y'";

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

    $order_by = 'maturity_date';
    $ord = 0;
    if (isset($_GET['ord'])) {
        $ord = $_GET['ord'];

        switch ($ord) {
            default:
                $order_by = 'maturity_date';
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

    $search_query .= " order by a.name asc, $order_by $order_type";

    $db = new db();
    $rs = $db->query($search_query);
    if ($rs === false) {
        $total_results = false;
    } else {
        $total_results = mysqli_num_rows($rs);
    }

    $namewise_fd_list = array();

    while ($row = mysqli_fetch_object($rs)) {
        $namewise_fd_list[$row->name][] = $row;
    }
?>

<table class="fd-list">
    <tr>
        <th rowspan="2" width="20px">#</th>
        <th rowspan="2" width="95px">
            <a href="?<?php echo$sort_link?>&ord=2&ot=<?php echo $ord == 2?$new_ot:$ot?>">Deposited In</a>
            <?php
                if ($ord == 2) {
                    if ($ot == 1) {
                        echo '&nbsp;<img height="10px" src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img height="10px" src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th rowspan="2" width="75px">
            <a href="?<?php echo$sort_link?>&ord=3&ot=<?php echo $ord == 3?$new_ot:$ot?>">Period</a>
            <?php
                if ($ord == 3) {
                    if ($ot == 1) {
                        echo '&nbsp;<img height="10px" src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img height="10px" src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th colspan="3">Dates</th>
        <th rowspan="2" width="50px">
            <a href="?<?php echo$sort_link?>&ord=6&ot=<?php echo $ord == 6?$new_ot:$ot?>">ROI</a>
            <?php
                if ($ord == 6) {
                    if ($ot == 1) {
                        echo '&nbsp;<img height="10px" src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img height="10px" src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th colspan="5">Amount</th>
        <th width="150px">Actions</th>
    </tr>
    <tr>
        <th width="80px">
            <a href="?<?php echo$sort_link?>&ord=4&ot=<?php echo $ord == 4?$new_ot:$ot?>">Deposite</a>
            <?php
                if ($ord == 4) {
                    if ($ot == 1) {
                        echo '&nbsp;<img height="10px" src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img height="10px" src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th width="80px">
            <a href="?<?php echo$sort_link?>&ord=5&ot=<?php echo $ord == 5?$new_ot:$ot?>">Renewal</a>
            <?php
                if ($ord == 5) {
                    if ($ot == 1) {
                        echo '&nbsp;<img height="10px" src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img height="10px" src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th width="80px">
            <a href="?<?php echo$sort_link?>&ord=0&ot=<?php echo $ord == 0?$new_ot:$ot?>">Maturity</a>
            <?php
                if ($ord == 0) {
                    if ($ot == 1) {
                        echo '&nbsp;<img height="10px" src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img height="10px" src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th width="65px">
            <a href="?<?php echo$sort_link?>&ord=7&ot=<?php echo $ord == 7?$new_ot:$ot?>">Deposite</a>
            <?php
                if ($ord == 7) {
                    if ($ot == 1) {
                        echo '&nbsp;<img height="10px" src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img height="10px" src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th width="65px">
            <a href="?<?php echo$sort_link?>&ord=8&ot=<?php echo $ord == 8?$new_ot:$ot?>">Interest</a>
            <?php
                if ($ord == 8) {
                    if ($ot == 1) {
                        echo '&nbsp;<img height="10px" src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img height="10px" src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th width="65px">Cur Int</th>
        <th width="65px">Cur Value</th>
        <th width="65px">
            <a href="?<?php echo$sort_link?>&ord=9&ot=<?php echo $ord == 9?$new_ot:$ot?>">Maturity</a>
            <?php
                if ($ord == 9) {
                    if ($ot == 1) {
                        echo '&nbsp;<img height="10px" src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img height="10px" src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
		<th><a href="javascript:void(0)" id="link_show_hide_all_details">Hide all details</a></th>
    </tr>

<?php
    $total_deposite_amount = 0;
    $total_interest_amount = 0;
    $total_matured_amount = 0;
    $total_cur_interest_amount = 0;
    $total_cur_value = 0;

    if (!$total_results) {
?>
        <tr class="even">
            <th colspan="13">Results are not found.</th>
        </tr>
<?php
    } else {
        $block = 1;
        foreach ($namewise_fd_list as $fd_name=>$fd_list) {
            echo '<tr>';
            echo '<td colspan="12" ><strong>' . $fd_name .'</strong></td>';
            echo '<td><a class="toggle_blocks" onclick="show_hide_block('.$block.')" href="javascript:void(0)">Show/Hide details</a></td>';
            echo '</tr>';

            $sr_no = 1;

            $namewise_deposite_amount = 0;
            $namewise_interest_amount = 0;
            $namewise_matured_amount = 0;
            $namewise_cur_interest_amount = 0;
            $namewise_cur_value = 0;

            foreach ($fd_list as $row) {
                $total_deposite_amount += $row->deposite_amount;
                $total_interest_amount += $row->total_interest;
                $total_matured_amount += $row->maturity_amount;

                $namewise_deposite_amount += $row->deposite_amount;
                $namewise_interest_amount += $row->total_interest;
                $namewise_matured_amount += $row->maturity_amount;

                if (strtotime($row->maturity_date) <= strtotime('now')) {
                    $renew_txt = '<a href="renew_fd.php?fdid='.$row->id.'">Renew</a> |';
                    $show_renew_alert = true;
                } else {
                    $show_renew_alert = false;
                    $renew_txt = 'Renew |';
                }
?>
                <tr class="<?php
                    if ($show_renew_alert) {
                        echo 'renew';
                    } else {
                        if ($sr_no % 2) { echo 'odd'; } else { echo 'even'; }
                    }
                ?> block_<?php echo $block ?>">
                <td align="right"><?php echo $sr_no++;?></td>
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
                <td align="right"><?php echo $fmt->format($row->rate_of_interest)?>%</td>
                <td align="right"><?php echo $fmt->format($row->deposite_amount) ?></td>
                <td align="right"><?php echo $fmt->format($row->total_interest) ?></td>
                <td align="right">
                    <?php
                        if (strtotime($row->maturity_date) <= strtotime('now')) {
                            // if maturity date is over
                            $int_till_date = $row->total_interest;
                        } else {
                            $int_till_date = get_current_interest_value(
                                $row->deposite_amount,
                                $row->renewal_date,
                                $row->maturity_date
                            );
                        }
                        echo $fmt->format($int_till_date);
                        $total_cur_interest_amount += $int_till_date;
                        $namewise_cur_interest_amount += $int_till_date;
                    ?>
                </td>
                <td align="right">
                    <?php
                        $cur_value = $row->deposite_amount + $int_till_date;
                        $total_cur_value += $cur_value;
                        $namewise_cur_value += $cur_value;
                        echo $fmt->format($cur_value);
                    ?>
                </td>
                <td align="right"><?php echo $fmt->format($row->maturity_amount) ?></td>
                <td align="left">
                    <?php echo $renew_txt ?> <a href="edit_fd.php?fdid=<?php echo $row->id?>">Edit</a> | <a href="delete_fd.php?fdid=<?php echo $row->id?>" onclick="return confirm('Are you sure to delete this FD?')">Delete</a>
                </td>
            </tr>
<?php
            }
?>
            <tr>
                <th colspan="7">Total</th>
                <th align="right"><?php echo $fmt->format($namewise_deposite_amount) ?></th>
                <th align="right"><?php echo $fmt->format($namewise_interest_amount) ?></th>
                <th align="right"><?php echo $fmt->format($namewise_cur_interest_amount) ?></th>
                <th align="right"><?php echo $fmt->format($namewise_cur_value) ?></th>
                <th align="right"><?php echo $fmt->format($namewise_matured_amount)?></th>
                <th>&nbsp;</th>
            </tr>
<?php
            $block++;
        }
    }
?>
    <tr>
        <th colspan="7">Final Total</th>
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
