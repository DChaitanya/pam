<?php
    session_start();
    if (!$_SESSION['is_logged']) {
        header("Location: login.php?redirect=index");
    }

    $page_title = "FY Report";
    include("header.php");
    include_once("db_connect.php");
    require_once('ajax.php');

    $current_financial_year = get_current_financial_year();

    $sort_link = '';

    if (isset($_GET['fy']) && is_numeric($_GET['fy'])) {
        $financial_year = trim($_GET['fy']);
        $sort_link = "&fy=$financial_year";
    } else {
        $financial_year = $current_financial_year;
    }

    //echo "Current financial year is " , $current_financial_year , ' - ' , $current_financial_year+1;

    $search_query = "
    select ah.*, au.name, s.scheme_name
    from accounts_history ah
    inner join acc_users au on ah.name = au.id
    inner join deposite_schemes s on ah.deposite_scheme = s.id
    where (
        (ah.maturity_date between '". $financial_year ."-04-01' and '". ($financial_year+1) ."-03-31' and ah.closed_date is null)
        or ah.closed_date between '". $financial_year ."-04-01' and '". ($financial_year+1) ."-03-31'
    )";
    //echo $search_query;

    $name = '';
    $deposite_scheme = '';
    $deposited_on = '';
    $matured_on = '';

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

    $order_by = '';
    $ord = 0;
    if (isset($_GET['ord'])) {
        $ord = $_GET['ord'];

        switch ($ord) {
            case 0:
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
            case 10:
                $order_by = 'account_id';
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

    if ($order_by) {
        $search_query .= " order by ah.name asc, $order_by $order_type, maturity_date asc";
    } else {
        $search_query .= " order by ah.name asc, maturity_date asc";
    }

    //echo $search_query;
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
        <td colspan="12">
            <form action="" method="GET" id="search_frm"><strong>FD Report for Financial Year:</strong>
                <select name="fy">
                    <option value="<?php echo $current_financial_year ?>" <?php if ($financial_year == $current_financial_year) echo "selected"?>><?php echo $current_financial_year, '-', $current_financial_year+1 ?></option>
                    <option value="<?php echo $current_financial_year-1 ?>" <?php if ($financial_year == $current_financial_year-1) echo "selected"?>><?php echo $current_financial_year-1, '-', $current_financial_year ?></option>
                    <option value="<?php echo $current_financial_year-2 ?>" <?php if ($financial_year == $current_financial_year-2) echo "selected"?>><?php echo $current_financial_year-2, '-', $current_financial_year-1 ?></option>
                </select>
                <input type="submit" name="submit" value="Submit" />
                <input type="hidden" name="ord" value="<?php echo $ord?>" />
                <input type="hidden" name="ot" value="<?php echo $ot?>" />
            </form>
        </td>
    </tr>
    <tr>
        <th rowspan="2" width="60px">#</th>
        <th rowspan="2" width="120px">
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
        <th rowspan="2" width="180px">
            <a href="?<?php echo$sort_link?>&ord=10&ot=<?php echo $ord == 10?$new_ot:$ot?>">Account #</a>
            <?php
                if ($ord == 10) {
                    if ($ot == 1) {
                        echo '&nbsp;<img height="10px" src="./media/images/asc.png"/>';
                    } else {
                        echo '&nbsp;<img height="10px" src="./media/images/desc.png"/>';
                    }
                }
            ?>
        </th>
        <th colspan="3">Dates</th>
        <th rowspan="2" width="100px">
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
        <th rowspan="2" width="100px">
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
        <th colspan="3">Amount</th>
        <th>Remark</th>
    </tr>
    <tr>
        <th width="100px">
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
        <th width="100px">
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
        <th width="100px">
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
        <th width="100px">
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
        <th width="100px">
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
        <th width="100px">
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

    if (!$total_results) {
?>
        <tr class="even">
            <th colspan="12">Results are not found.</th>
        </tr>
<?php
    } else {
        $block = 1;
        foreach ($namewise_fd_list as $fd_name=>$fd_list) {
            echo '<tr>';
            echo '<td colspan="11" ><strong>' . $fd_name .'</strong></td>';
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

?>
                <tr class="<?php echo $sr_no % 2? 'odd' : 'even'; ?> block_<?php echo $block ?>">
                <td align="right"><?php echo $sr_no++; ?></td>
                <td align="left"><?php echo $row->scheme_name; ?></td>
                <td align="right">
                	<?php
                		if ($row->ref_id) {
                			echo $row->ref_id;
                		} else {
                			printf("%08d", $row->account_id);
                		}
                	?>
                </td>

                <td align="right"><?php echo date("d-m-Y", strtotime($row->deposite_date)) ?></td>
                <td align="right"><?php echo date("d-m-Y", strtotime($row->renewal_date)) ?></td>
                <td align="right"><?php echo date("d-m-Y", strtotime($row->maturity_date)) ?></td>
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
                <td align="right"><?php echo $fmt->format($row->rate_of_interest)?>%</td>
                <td align="right"><?php echo $fmt->format($row->deposite_amount) ?></td>
                <td align="right"><?php echo $fmt->format($row->total_interest) ?></td>
                <td align="right"><?php echo $fmt->format($row->maturity_amount) ?></td>
                <td align="left"><?php echo ucfirst($row->action); ?></td>
            </tr>
<?php
            }
?>
            <tr>
                <th colspan="8">Total</th>
                <th align="right"><?php echo $fmt->format($namewise_deposite_amount) ?></th>
                <th align="right"><?php echo $fmt->format($namewise_interest_amount) ?></th>
                <th align="right"><?php echo $fmt->format($namewise_matured_amount)?></th>
                <th>&nbsp;</th>
            </tr>
<?php
            $block++;
        }
    }
?>
    <tr>
        <th colspan="8">Final Total</th>
        <th align="right"><?php echo $fmt->format($total_deposite_amount) ?></th>
        <th align="right"><?php echo $fmt->format($total_interest_amount) ?></th>
        <th align="right"><?php echo $fmt->format($total_matured_amount)?></th>
        <th>&nbsp;</th>
    </tr>
</table>

<?php
    include("footer.php");
?>
