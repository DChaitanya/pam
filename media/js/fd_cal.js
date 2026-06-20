function cal_maturity_amount() {
    var amount_of_deposite = $("#deposite_amount").val();
    var roi = $("#rate_of_interest").val();
    var it = $("#interest_type").val();
    var deposite_type = $("#deposite_type").val();
    var period = $("#period").val();
    var period_type = $("#period_type").val();

    if (amount_of_deposite && roi &&  period && period_type && amount_of_deposite && deposite_type && it) {
        $.ajax({
            type: "POST",
            url: "./ajax.php?action=cal_maturity_amount",
            data: "deposite_amount=" + amount_of_deposite +
                  "&interest_type=" + it +
                  "&deposite_type=" + deposite_type +
                  "&rate_of_interest=" + roi +
                  "&period=" + period +
                  "&period_type=" + period_type,

            success: function(data) {
                var datalist = data.split("|");
                var total_interest = datalist[0];
                var amount_of_maturity = datalist[1];

                $("#total_interest").val(total_interest);
                $("#maturity_amount").val(amount_of_maturity);
            }
        });
    }
}

function cal_maturity_date() {
    var date_of_deposite = $('#deposite_date').val();
    var period = $("#period").val();
    var period_type = $("#period_type").val();

    if (date_of_deposite && period && period_type) {
        $.ajax({
            type: "POST",
            url: "./ajax.php?action=cal_maturity_date",
            data: "deposite_date=" + date_of_deposite +
                  "&period=" + period +
                  "&period_type=" + period_type,
            success: function(data) {
                $("#maturity_date").val(data);
            }
        });
    }
}

function cal_maturity_date1() {
    var date_of_deposite = $('#renewal_date').val();
    var period = $("#period").val();
    var period_type = $("#period_type").val();

    if (date_of_deposite && period && period_type) {
        $.ajax({
            type: "POST",
            url: "./ajax.php?action=cal_maturity_date",
            data: "deposite_date=" + date_of_deposite +
                  "&period=" + period +
                  "&period_type=" + period_type,
            success: function(data) {
                $("#maturity_date").val(data);
            }
        });
    }
}

function show_hide_block(block_id) {
    $('.block_'+block_id).toggle();
}

function show_all_blocks() {
    $('.odd').show();
    $('.even').show();
}

function hide_all_blocks() {
    $('.odd').hide();
    $('.even').hide();
}

function show_hide_all_details(obj) {
    if (obj.html() == 'Hide all details') {
		hide_all_blocks();
		obj.html('Show all details');
	} else {
		show_all_blocks();
		obj.html('Hide all details');
	}
}