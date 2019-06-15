<body style="padding:15px;font-family: Arial;">
<span id="amazing">
<style type="text/css">
body { font-family:Helvetica !important; }
table {font-family:Helvetica !important;border-spacing: 0; font-size:11px; text-align:center;vertical-align:center;}
th, td { vertical-align:center;text-align:center;border-top: 1px solid #d7d7d7;  border-left: 1px solid #d7d7d7;  border-bottom: 1px solid #d7d7d7;  border-right: 1px solid #d7d7d7;  padding: 0px; }
</style>
	<div class="responsive" style="padding:15px;">
		<table class="table table-condensed" width="100%">
		<tr><th colspan="6" style="font-family:Helvetica;text-align:left;font-size:11px;border:none;">Report Code: vhis 14</th></tr>
		<tr><th colspan="6" style="text-align:center;font-family:Helvetica;font-size:22px;border:none;">IVRS System Availability</th></tr>
		<tr><th colspan="6" style="font-family:Helvetica;text-align:right;font-size:11px;border:none;">Report Interval: <?php echo date('m/d/Y H:i:s', strtotime($df))." - ". date('m/d/Y H:i:s', strtotime($dt)); ?></th></tr>
	</table><br/>
		<table class="table table-condensed" style="width:100%;border:1px solid #d7d7d7">
		<tr style="background-color:#cacaca;font-size:11px;font-family:Helvetica;">
			<th>Assigned Application</th>
			<th>Status</th>
			<th>Target Time Available (in minutes)</th>
			<th>Actual Time Available (in minutes)</th>
			<th>% Availability</th>
			<th>Date/Time of Last Transaction</th></tr>
		<?php foreach($reportdata->result_object() as $key => $value){ ?>
		<tr style="font-size:11px;font-family:Helvetica;font-weight:normal;">
			<td><?php echo $value->AssignedApplication?></td>
			<td><?php if($value->{'Actual Time Available (minutes)'}=='.00'){ echo "Down"; }else{ echo "Active"; }?></td>
			<td><?php if($value->{'Target Time Available (minutes)'}=='.00'){ echo "0.00"; }else{ echo number_format($value->{'Target Time Available (minutes)'},2); }?></td>
			<td><?php if($value->{'Actual Time Available (minutes)'}=='.00'){ echo ($value->{'Actual Time Available (minutes)'}== '' ? number_format($value->{'Target Time Available (minutes)'},2) :  ($value->{'Actual Time Available (minutes)'} < 0 ? '0.00' : number_format($value->{'Actual Time Available (minutes)'},2))); }else{ echo ($value->{'Actual Time Available (minutes)'}== '' ? number_format($value->{'Target Time Available (minutes)'},2) :  ($value->{'Actual Time Available (minutes)'} < 0 ? 0 :number_format($value->{'Actual Time Available (minutes)'},2)));}?></td>
			<td><?php echo ($value->{'Actual Time Available (minutes)'}== '' ? '100.00%' :  ($value->{'Actual Time Available (minutes)'}<0 ? '0.00%' :number_format($value->{'Actual Time Available (minutes)'}/ $value->{'Target Time Available (minutes)'}  *100,2) .'%'));?></td>
			<td><?php echo $value->{'LastDateTimeTransaction'}?><span style="color:#fff">`</span></td>
		</tr> <?php  }?>
	</table>
	</div> <br/> <br/>
<table><tr><td style='text-align:center;font-family: Helvetica;font-size: 11px; border: none;'>Printed By: <?php echo ($this->input->post('fullname') != "" ? $this->input->post('fullname'):$this->session->userdata('Fullname'))  . ': ' . date('m/d/Y H:i:s'); ?></td></tr></table>
</span>
	<div class="container-fluid" style="<?php  echo ($this->input->post("format") <> "" ? "display: none;" : "" ); ?>">
	<div class="content">
		<br/><br/><h3>Export:</h3>
		<div id="buttons"><input type="button" class="btn btn-success" onclick="exportPDF();" value="PDF"> <input type="button" class="btn btn-success" onclick="exportXLS()" value="EXCEL"> <input type="button" class="btn btn-success" onclick="exportCSV()" value="CSV"> <input type="button" class="btn btn-success" onclick="exportDOC();" value="WORD"> <input type="button" class="btn btn-success" onclick="exportHTML();" value="HTML">	</div>
		<form id="exportPDF" action="<?php echo base_url("index.php/report_controller_z/generatePDF"); ?>" method="post" target="_blank"><textarea id="pdfdata" name="html" style="display:none"></textarea><input type="hidden" name="orient" value="1"></form>
		<form id="exportXLS" action="<?php echo base_url("index.php/report_controller_z/generateXLS"); ?>" method="post" target="_blank"><textarea id="exceldata" name="html" style="display:none"></textarea><textarea id="excelcss" name="css" style="display:none"></textarea></form>
		<form id="exportDOC" action="<?php echo base_url("index.php/report_controller_z/generateDOC"); ?>" method="post" target="_blank"><textarea id="docdata" name="html" style="display:none"></textarea><textarea id="htmlcss" name="css" style="display:none"></textarea></form>
		<form id="exportHTML" action="<?php echo base_url("index.php/report_controller_z/generateHTML"); ?>" method="post" target="_blank"><textarea id="htmldata" name="html" style="display:none"></textarea></form>
		<form id="exportXLSServer" action="<?php echo base_url("index.php/report_controller_z/generateXLSServer"); ?>" method="post"><textarea id="exceldataserver" name="html" style="display:none"></textarea><textarea id="excelcssserver" name="css" style="display:none"></textarea></form>
		<form id="exportPDFServer" action="<?php echo base_url("index.php/report_controller_z/generatePDFServer"); ?>" method="post"><textarea id="pdfdataserver" name="html" style="display:none"></textarea></form>
		<form id="exportDOCServer" action="<?php echo base_url("index.php/report_controller_z/generateDOCServer"); ?>" method="post"><textarea id="docdataserver" name="html" style="display:none"></textarea><textarea id="htmlcssserver" name="css" style="display:none"></textarea></form>
		<form id="exportHTMLServer" action="<?php echo base_url("index.php/report_controller_z/generateHTMLServer"); ?>" method="post"><textarea id="htmldataserver" name="html" style="display:none"></textarea></form>
		<form id="exportCSVServer" action="<?php echo base_url("index.php/report_controller_z/generateCSVServer"); ?>" method="post"><textarea id="csvdataserver" name="html" style="display:none"></textarea></form>
	</div>		
</div>
<div id="lol" style="display:none;"></div>
<script type="text/javascript">
	$(document).ready( function () {
		<?php if($this->input->post('scheduled') == true): ?>
		<?php if($this->input->post('format') == "PDF"): ?>
			$("#lol").text($("#amazing").html());$("#pdfdataserver").text($("#lol").text());
			setTimeout(function() {	$("#exportPDFServer").submit();	},500);
		<?php elseif($this->input->post('format') == "WORD"): ?>
			$("#lol").text($("#amazing").html());$("#docdataserver").text($("#lol").text()); $("#htmlcssserver").text($("#css").text());
			setTimeout(function() {	$("#exportDOCServer").submit();	},500);
		<?php elseif($this->input->post('format') == "HTML"): ?>
			$("#lol").text($("#amazing").html());$("#htmldataserver").text($("#lol").text());
			setTimeout(function() {	$("#exportHTMLServer").submit();},500);
		<?php elseif($this->input->post('format') == "CSV"): ?>
			var csv = [];
			var rows = document.querySelectorAll("table tr");
			for (var i = 0; i < rows.length; i++) {
			var row = [], cols = rows[i].querySelectorAll("td, th");
			
			for (var j = 0; j < cols.length; j++) {
				var str = cols[j].innerText;
				if (str.includes('`')){
				row.push('="' + cols[j].innerText.replace("`","") + '"');
				}else if (str.includes(',')){
				row.push('="' + cols[j].innerText.replace(/,/g," ") + '"');
				}else{
				row.push('="' + cols[j].innerText + '"');
				}
			}
			csv.push(row.join(","));        
		}
			var finaldata = csv.join("\n");
			$("#csvdataserver").text(finaldata);
			setTimeout(function() {	$("#exportCSVServer").submit();	},500);
		<?php else: ?>
			$("#lol").text($("#amazing").html()); $("#exceldataserver").text($("#lol").text());	$("#excelcssserver").text($("#css").text());
			setTimeout(function() {	$("#exportXLSServer").submit();	},500);
		<?php endif;endif; ?>	
	});
	function exportCSV() {
		exportTableToCSV('Report'+<?php echo date("YmdHis"); ?>+'.csv')
	}
	function exportXLS() {
		$("#lol").text($("#amazing").html());$("#exceldata").text($("#lol").text());$("#excelcss").text($("#css").text());setTimeout(function() {$("#exportXLS").submit();},500);
	}
	function exportPDF() {$("#lol").text($("#amazing").html());$("#pdfdata").text($("#lol").text());setTimeout(function() {	$("#exportPDF").submit();},500);
	}
	function exportDOC() {$("#lol").text($("#amazing").html());$("#docdata").text($("#lol").text());$("#htmlcss").text($("#css").text());setTimeout(function() {$("#exportDOC").submit();},500);
	}
	function downloadCSV(csv, filename) {
		var csvFile;var downloadLink;
		csvFile = new Blob([csv], {type: "text/csv"});
		downloadLink = document.createElement("a");
		downloadLink.download = filename;
		downloadLink.href = window.URL.createObjectURL(csvFile);
		downloadLink.style.display = "none";
		document.body.appendChild(downloadLink);
		downloadLink.click();
	}
	function exportHTML() {$("#lol").text($("#amazing").html());$("#htmldata").text($("#lol").text());setTimeout(function() { $("#exportHTML").submit(); },500);
	}
	function exportTableToCSV(filename) {
		var csv = [];
		var rows = document.querySelectorAll("table tr");
		for (var i = 0; i < rows.length; i++) {
			var row = [], cols = rows[i].querySelectorAll("td, th");
			
			for (var j = 0; j < cols.length; j++) {
				var str = cols[j].innerText;
				if (str.includes('`')){
				row.push('="' + cols[j].innerText.replace("`","") + '"');
				}else if (str.includes(',')){
				row.push('="' + cols[j].innerText.replace(/,/g," ") + '"');
				}else{
				row.push('="' + cols[j].innerText + '"');
				}
			}
			csv.push(row.join(","));        
		}
		downloadCSV(csv.join("\n"), filename);
		
	}
</script>

</body>
</html>