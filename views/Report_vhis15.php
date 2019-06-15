<body style="padding:15px;font-family: Arial;">
<span id="amazing">
<style type="text/css">
body { font-family:Helvetica !important; }
table {font-family:Helvetica !important;border-spacing: 0; font-size:11px; text-align:center;vertical-align:center;}
th, td { vertical-align:center;text-align:center;border-top: 1px solid #d7d7d7;  border-left: 1px solid #d7d7d7;  border-bottom: 1px solid #d7d7d7;  border-right: 1px solid #d7d7d7;  padding: 0px; }
</style>
	<div class="responsive" style="padding:15px;">
		<table class="table table-condensed" width="100%">
			<tr align="Left"><th colspan="9" style="text-align:left; font-family: Helvetica;font-size: 11px; border:none;">Report Code: Vhis 15</th></tr>
			<tr align="Center"><th colspan="9" style="text-align:center; border: none;"><span style="font-family: Helvetica;font-size: 22px">Trunk Availability</span></th></tr>
			<tr align="Right"><th colspan='9' style="text-align:right;font-family: Helvetica; font-size:11px; border: none;">Report Interval: <?php  echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt));?></th></tr></table><br/>
			<table cellpadding="0" cellspacing="0" id="vhis15" class="table table-bordered table-condensed" width="100%" >
				<tr style="font-family:Helvetica;font-size:11px;border: 1px solid #d7d7d7;background-color:#cacaca;">
					<th style="font-family:Helvetica;font-size:11px; text-align: center;"colspan='2'>Trunk Id/Name</th><th style="font-family:Helvetica;font-size:11px; text-align: center;">Assigned Application</th>
					<th style="font-family:Helvetica;font-size:11px; text-align: center;">Capacity</th><th style="font-family:Helvetica;font-size:11px; text-align: center;">Status</th>
					<th style="font-family:Helvetica;font-size:11px; text-align: center;">Target Time Available (in minutes)</th><th style="font-family:Helvetica;font-size:11px; text-align: center;">Actual Time Available (in minutes)</th>
					<th style="font-family:Helvetica;font-size:11px; text-align: center;">% Availability</th><th style="font-family:Helvetica;font-size:11px; text-align: center;">Date/Time of Last Transaction</th>
				</tr>
				<?php foreach($result as $data): ?>
				<tr id="dataResult" style="font-family:Helvetica;font-size:11px;">
					<td style="font-family:Helvetica;font-size:11px; text-align: center;"colspan='2'><?php echo $data->TrunkIdName; ?></td>
					<td style="font-family:Helvetica;font-size:11px; text-align: center;"><?php echo $data->AssignedApplication; ?></td>
					<td style="font-family:Helvetica;font-size:11px; text-align: center;"><?php echo $data->Capacity; ?></td>
					<td style="font-family:Helvetica;font-size:11px; text-align: center;"><?php echo $data->Status; ?></td>
					<td style="font-family:Helvetica;font-size:11px; text-align: center;"><?php echo number_format(round($data->TargetTimeAvailable/60),2); ?></td>
					<td style="font-family:Helvetica;font-size:11px; text-align: center;"><?php echo  number_format(round($data->ActualTimeAvailable/60),2); ?></td>
					<td style="font-family:Helvetica;font-size:11px; text-align: center;"><?php if ($data->TargetTimeAvailable > 0) {echo number_format((($data->ActualTimeAvailable/$data->TargetTimeAvailable) * 100),2); }else{echo "0";}?>%</td>
					<td style="font-family:Helvetica;font-size:11px; text-align: center;"><?php if($data->LastDateTimeTransaction !=""){echo date('m/d/Y H:i:s',strtotime($data->LastDateTimeTransaction));} ?><span style="color:#fff">`</span></td> </tr> <?php endforeach; ?>
			</table>
	</div> <br/><br/>
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