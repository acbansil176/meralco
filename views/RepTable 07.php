<body>
<div id="amazing">
<style type="text/css">
body { font-family:Helvetica !important; }
table { border-spacing: 0; }
th, td { padding: 0px; }
.e{ border: 1px solid #d7d7d7  }
</style>
<div class="container-fluid" id="amazingxx">
<table class="table table-condensed" style="width:100%">
	<tr><th align='left' colspan="18" style="text-align:left;font-family: Helvetica; font-size:11px; border: none;">Report Code: Vhis 07</th></tr>
	<tr><th align="center" colspan="18" style="text-align:center;font-family: Helvetica; font-size:16px; border: none;">Outbound Voice - Callback</th></tr>
	<tr><th align="right" colspan="18" style="text-align:right;font-family: Helvetica; font-size:11px; border: none;">Report Interval:<?php 
		if($frequency=="Morning"){ echo date("m/d/Y 06:00:00 ",strtotime($df))." - ". date("m/d/Y 14:00:00",strtotime($dt));}
		elseif($frequency=="Afternoon"){echo date("m/d/Y 14:00:00 ",strtotime($df))." - ". date("m/d/Y 22:00:00",strtotime($dt));}
		elseif($frequency=="Graveyard"){	echo date("m/d/Y 22:00:00 ",strtotime($df))." - ". date("m/d/Y 06:00:00",strtotime($dt)); }
		elseif($frequency=="Fifteen"){
			if(date("H:i",strtotime($dt))!='23:59'){echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt));}else{echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y 23:45:00",strtotime($dt));	}
		}elseif($frequency=="Thirty"){
			if(date("H:i",strtotime($dt))!='23:59'){echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt)); }else{echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y 23:30:00",strtotime($dt)); }
		}elseif($frequency=="Sixty"){
			if(date("H:i",strtotime($dt))!='23:59'){echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt)); }else{echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y 23:00:00",strtotime($dt)); }
		}elseif($frequency=="W"){$day1 = date('w',strtotime($df));$day2 = date('w',strtotime($dt));echo date('m/d/Y H:i:s ', strtotime($df .' -'.$day1.' days'))." - ". date("m/d/Y 23:59:59" ,strtotime($dt .' +'.(6-$day2).' days'));}else{ echo date("m/d/Y H:i:s ",strtotime($df))." - ". date("m/d/Y H:i:s",strtotime($dt));}
		?></th></tr></table><br/>
<?php 	$sitearray = [];
	foreach($reportdata->result_object() as $obj){
		switch($frequency){
			case "Fifteen": $dateInterval = $obj->{'Minus15Mins'}; break;
			case "Thirty": $dateInterval = $obj->{'Minus30Mins'}; break;
			case "Sixty": $dateInterval = $obj->{'Minus60Mins'}; break;
			case "D": $dateInterval = $obj->perDate; break;
			case "W": $dateInterval = $obj->Sunday; break;
			case "M": case "PM": $dateInterval = $obj->MONTH_NAME; break;
			case "Y": $dateInterval = $obj->YEAR; break;
			case "Morning": $dateInterval = $obj->Morning; break;
			case "Afternoon": $dateInterval = $obj->Afternoon; break;
			case "Graveyard": $dateInterval = $obj->Graveyard; break;		
		}
		if($dateInterval != ""){
			$data[$obj->SiteName][$dateInterval][] = $obj;
			if($obj->Disposition_desc == ""){ $ccd = "No Disposition"; }else{ $ccd = $obj->Disposition_desc; }
			if($obj->ConcernType == ""){ $cct = "No Concern Type"; }else{ $cct = $obj->ConcernType; }
			$pieCallDisposition[$obj->SiteName][$ccd][] = $obj;
			$pieConcernType[$obj->SiteName][$cct][] = $obj;
			if(!in_array($obj->SiteName,$sitearray)){ array_push($sitearray, $obj->SiteName); }
		}
	}
	foreach($sitearray as $siteIN){  ?>		
<table class="table table-condensed e"  style="font-family:Helvetica; font-size:11px;width:100%;">
	<tr> <th colspan="18" style='font-weight: bold;;background-color:#36CCF8;text-align:left;'>Site Name: <?php echo $siteIN; ?></th> </tr>
	<tr style='text-align:center;font-family: Helvetica;font-size: 11px;background-color:#cacaca;'>
		<th class="e" colspan="2">Interval</th> <th class="e"  colspan="2">SIN</th> <th class="e"  colspan="2">Date of Request</th> <th class="e"  colspan="2">Time of Request</th>
		<th class="e"  colspan="2">Callback Date</th> <th  class="e" colspan="2">Callback Time</th> <th class="e"  colspan="2">Landline Number</th> <th class="e"  colspan="2">Call Disposition</th> <th  class="e" colspan="2">Concern Type</th> </tr>
<?php		
	foreach($data[$siteIN] as $key => $value){
	$topData = 0; $rowcount = 0;
	foreach($value as $tableplot){ $rowcount++; }
		foreach($value as $tableplot){
			switch($frequency){
				case "Fifteen": $dateIntervalplot = $tableplot->{'Minus15Mins'}; $adder = " - ".date("H:i:s",strtotime($tableplot->{'15MinsInterval'})); break;
				case "Thirty": $dateIntervalplot = $tableplot->{'Minus30Mins'}; $adder = " - ".date("H:i:s",strtotime($tableplot->{'30MinsInterval'})); break;
				case "Sixty": $dateIntervalplot = $tableplot->{'Minus60Mins'}; $adder = " - ".date("H:i:s",strtotime($tableplot->{'60MinsInterval'})); break;
				case "D": $dateIntervalplot = $tableplot->perDate; $adder = ""; break;
				case "W": $dateIntervalplot = $tableplot->Sunday; $adder =  " - ".date("m/d/Y",strtotime($tableplot->Saturday)); break;
				case "M": case "PM": $dateIntervalplot = $tableplot->MONTH_NAME; $adder = ""; break;
				case "Y": $dateIntervalplot = $tableplot->YEAR; $adder = ""; break;
				case "Morning": $dataexploder = explode(" - ",$tableplot->Morning); $dateIntervalplot = $tableplot->Morning; $datex = (date("m/d/Y H:i:s",strtotime( $dataexploder[0]))); $adder = " - ".date("m/d/Y H:i:s",strtotime( $datex ."+8 Hours")); break;
				case "Afternoon": $dataexploder = explode(" - ",$tableplot->Afternoon); $dateIntervalplot = $tableplot->Afternoon; $datex = (date("m/d/Y H:i:s",strtotime( $dataexploder[0]))); $adder = " - ".date("m/d/Y H:i:s",strtotime( $datex ."+8 Hours")); break;
				case "Graveyard": $dataexploder = explode(" - ",$tableplot->Graveyard); $dateIntervalplot = $tableplot->Graveyard; $datex = (date("m/d/Y H:i:s",strtotime( $dataexploder[0]))); $adder = " - ".date("m/d/Y H:i:s",strtotime( $datex ."+8 Hours")); break;
			}	
		echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;'>";
			if($key == $dateIntervalplot && $topData == 0){
				if($frequency == "Morning" ||$frequency == "Afternoon" ||$frequency == "Graveyard"){ echo "<td colspan='2' style='text-align:center;border-top: 1px solid #d7d7d7;border-left: 1px solid #d7d7d7;border-right: 1px solid #d7d7d7;border-bottom:1px solid #d7d7d7; border-bottom: none;'>".$datex.$adder."</td>"; }
					elseif($frequency == "Fifteen" ||$frequency == "Thirty" ||$frequency == "Sixty"){ echo "<td colspan='2' style='text-align:center;border-top: 1px solid #d7d7d7;border-left: 1px solid #d7d7d7;border-right: 1px solid #d7d7d7;border-bottom:1px solid #d7d7d7; border-bottom: none;'>".date("m/d/Y H:i:s",strtotime($key)).$adder."</td>"; }
					elseif($frequency == "D" ||$frequency == "W" ){ echo "<td colspan='2' style='text-align:center;border-top: 1px solid #d7d7d7;border-left: 1px solid #d7d7d7;border-right: 1px solid #d7d7d7;border-bottom:1px solid #d7d7d7; border-bottom: none;'>".date("m/d/Y",strtotime($key)).$adder."</td>"; }
					else{ echo "<td  colspan='2' style='text-align:center;border-top: 1px solid #d7d7d7;border-left: 1px solid #d7d7d7;border-right: 1px solid #d7d7d7;border-bottom:1px solid #d7d7d7; border-bottom: none;'>".$key.$adder."</td>"; }
				$topData = 1;
			} else { echo "<td colspan='2' style='border-left: 1px solid #d7d7d7;border-right: 1px solid #d7d7d7;'></td>"; }
		echo "<td  class='e' colspan='2'><span style='color:#fff;'>`</span>".$tableplot->SIN."</td> <td class='e' colspan='2'>".date("m/d/Y",strtotime($tableplot->Call1_dt))."</td> <td  class='e' colspan='2'>".date("H:i:s",strtotime($tableplot->Call1_dt))."</td>";
			if($tableplot->CallStartDt == ""){ $cbdate = ""; $cbtime = ""; }
				else{ $cbdate = date("m/d/Y",strtotime($tableplot->CallStartDt)); $cbtime = date("H:i:s",strtotime($tableplot->CallStartDt)); }
		echo "<td  class='e' colspan='2'>".$cbdate."</td> <td  class='e' colspan='2'>".$cbtime."</td> <td  class='e' colspan='2'>".$tableplot->LandlineNumber."</td> <td  class='e' colspan='2'>".$tableplot->Disposition_desc."</td> <td  class='e' colspan='2'>".$tableplot->ConcernType."</td> </tr>";
		}
	}
echo "<tr class='e' ><td style='padding:0px' colspan='18'></td></tr> </table>"; ?>

<table width="100%" style="border:none;"> <tr style="border:none;"><br/>
<td style="border:none;" rowspan='20' colspan="7"><div id='chartdiv<?php echo preg_replace('/\s+/', '_', $siteIN); ?>' style="width:555px; height:300px;"></div></td>
<td style="border:none;" rowspan='20'><div id='chartdiv2<?php echo preg_replace('/\s+/', '_', $siteIN); ?>'  style="width:655px; height:300px;"></div></td> </tr> </table>

<table style="visibility:hidden"> <tr><th></th></tr> <tr><th></th></tr> </table>
<?php } ?> <br>
<table><tr><td style="border:none"><?php echo "<span style='font-family:Helvetica;font-size:11px;'>Printed By: " . ($this->input->post('fullname') != "" ? $this->input->post('fullname'):$this->session->userdata('Fullname'))  . ": " .date('m/d/Y H:i:s') . '</span>'; ?></td></tr></table>
</div> </div>

	<div class="content" style="<?php  echo ($this->input->post("format") <> "" ? "display: none;" : "" ); ?>"><h3>Export:</h3>
		<div style="float:left; padding-right:10px;">
			<input type="button" class="btn btn-success" onclick="exportPDF();" value="PDF"> <input type="button" class="btn btn-success" onclick="exportXLS()" value="EXCEL"> <input type="button" class="btn btn-success" onclick="exportCSV()" value="CSV"> <input type="button" class="btn btn-success" onclick="exportDOC();" value="WORD"> <input type="button" class="btn btn-success" onclick="exportHTML();" value="HTML">
			<form id="exportPDF" action="<?php echo base_url("index.php/report_controller_z/generatePDF"); ?>" method="post" target="_blank"> <textarea id="pdfdata" name="html" style="display:none"></textarea> <input type="hidden" name="orient" value="1"> </form>
			<form id="exportXLS" action="<?php echo base_url("index.php/report_controller_z/excel"); ?>" method="post" target="_blank"> <textarea id="exceldata" name="xhtml" style="display:none"></textarea> <textarea id="excelcss" name="css" style="display:none"></textarea> </form>
			<form id="exportDOC" action="../report_controller_z/generateDOC"" method="post" target="_blank"> <textarea id="docdata" name="html" style="display:none"></textarea> <textarea id="htmlcss" name="css" style="display:none">gg</textarea> <input type="hidden" name="orient" value="1"> </form>
			<form id="exportHTML" action="../report_controller_z/generateHTML" method="post" target="_blank"> <textarea id="htmldata" name="html" style="display:none"></textarea> </form>
		</div>
			<form id="exportXLSServer" action="<?php echo base_url("index.php/report_controller_z/generateXLSServer"); ?>" method="post"> <textarea id="exceldataserver" name="html" style="display:none"></textarea> <textarea id="excelcssserver" name="css" style="display:none"></textarea> </form>
			<form id="exportPDFServer" action="<?php echo base_url("index.php/report_controller_z/generatePDFServer"); ?>" method="post"> <textarea id="pdfdataserver" name="html" style="display:none"></textarea> </form>
			<form id="exportDOCServer" action="<?php echo base_url("index.php/report_controller_z/generateDOCServer"); ?>" method="post"> <textarea id="docdataserver" name="html" style="display:none"></textarea> <textarea id="htmlcssserver" name="css" style="display:none"></textarea> </form>
			<form id="exportHTMLServer" action="<?php echo base_url("index.php/report_controller_z/generateHTMLServer"); ?>" method="post"> <textarea id="htmldataserver" name="html" style="display:none"></textarea> </form>
			<form id="exportCSVServer" action="<?php echo base_url("index.php/report_controller_z/generateCSVServer"); ?>" method="post"> <textarea id="csvdataserver" name="html" style="display:none"></textarea> </form>
		<div id="lol" style="display:none;"></div>
	</div>
<input type='hidden' id='donecanvas' value="not done" /></body>
<script src="<?php echo base_url(); ?>assets/js/html2canvas.min.js"></script> 
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jspdf.debug.js"></script>
<script type="text/javascript">
<?php foreach($sitearray as $siteIN){
			$calldispochart = ""; $total = 0;
			foreach($pieCallDisposition[$siteIN] as $keycd => $valcd){ 
				foreach($valcd as $cd){ $total++; }
			}
			foreach($pieCallDisposition[$siteIN] as $keycd => $valcd){
				$count = 0;
				foreach($valcd as $cd){ $count++; }
				if($total == 0){ $per = '0%'; }
					else{ $per = round(($count/$total)*100 , 2).'%'; } 
				$calldispochart .= '{ "label": "'. '  '. $keycd .' : ' . $count .' - '.$per.' ", "data": "'.$count.'"}, '; 
			}
			$calldispochart = rtrim($calldispochart,","); $concerntypechart =""; $total = 0;
			foreach($pieConcernType[$siteIN] as $keyct => $valct){			
				foreach($valct as $tt){ $total++; } 
			}
			foreach($pieConcernType[$siteIN] as $keyct => $valct){
				$count = 0;
				foreach($valct as $tt){ $count++; }
				if($total == 0){ $per = '0%'; }else{ $per = round(($count/$total)*100 , 2).'%'; }
				$concerntypechart .= '{ "label": "'. '  '. $keyct .' : ' . $count .'- '.$per.' ", "data": "'.$count.'"}, ';
			}
			$calldispochart = rtrim($calldispochart,","); ?>
	var data = [ <?php echo $calldispochart; ?> ];
	$.plot('#chartdiv<?php echo preg_replace('/\s+/', '_', $siteIN); ?>', data, { series: { pie: { show: true, label: { show: true, radius: 20/20, threshold: 2, formatter: function (label, series) { var element = '<div style="font-size:8pt; text-align:center;padding:2px;">' + label + '<br/>' + series.data[0][1] + '</div>'; return element; } } } }, legend: { show: true, margin:20 } });
	toCanvas('chartdiv<?php echo preg_replace('/\s+/', '_', $siteIN); ?>');
	var data = [ <?php echo $concerntypechart; ?> ];
    $.plot('#chartdiv2<?php echo preg_replace('/\s+/', '_', $siteIN); ?>', data, { series: { pie: { show: true, label: { show: true, radius: 10/10, threshold: 2, formatter: function (label, series) { var element = '<div style="font-size:8pt; text-align:center;padding:2px;">' + label + '<br/>' + series.data[0][1] + '</div>'; return element; } } } }, legend: { show: true, margin:20 } });
	toCanvas('chartdiv2<?php echo preg_replace('/\s+/', '_', $siteIN); ?>');
<?php  } ?>		
	$('#donecanvas').val('done');
$(document).ready(function() {
		<?php if($this->input->post('scheduled') == true): ?>
		<?php if($this->input->post('format') == "xPDF"): ?>
			setTimeout(function() { $("#lol").text($("#amazing").html()); $("#pdfdataserver").text($("#lol").text()); $("#exportPDFServer").submit(); },25000);
		<?php elseif($this->input->post('format') == "WORD"): ?>
			setTimeout(function() { $("#lol").text($("#amazing").html()); $("#docdataserver").text($("#lol").text()); $("#htmlcssserver").text($("#css").text()); $("#exportDOCServer").submit(); },25000);
		<?php elseif($this->input->post('format') == "HTML"): ?>
			setTimeout(function() { $("#lol").text($("#amazing").html()); $("#htmldataserver").text($("#lol").text()); $("#exportHTMLServer").submit(); },25000);
		<?php elseif($this->input->post('format') == "CSV"): ?>
			var csv = []; var rows = document.querySelectorAll("table tr");
			for (var i = 0; i < rows.length; i++) {
				var row = [], cols = rows[i].querySelectorAll("td, th");
				for (var j = 0; j < cols.length; j++) 
				var str = cols[j].innerText;
				if (str.includes('`')){
				row.push('="' + cols[j].innerText.replace("`","") + '"');
				}else if (str.includes(',')){
				row.push('="' + cols[j].innerText.replace(/,/g," ") + '"');
				}else{
				row.push('="' + cols[j].innerText + '"');
				}
				csv.push(row.join(","));        
			}
			var finaldata = csv.join("\n");
			$("#csvdataserver").text(finaldata); 
			setTimeout(function() { $("#exportCSVServer").submit(); },2500);
		<?php else: ?>
			setTimeout(function() { $("#lol").text($("#amazing").html()); $("#exceldataserver").text($("#lol").text()); $("#excelcssserver").text($("#css").text()); $("#exportXLSServer").submit(); },25000);
		<?php endif; ?>
		<?php endif; ?>
});
	$('#export').click(function(){ $('#html').val($('#amazing').html()); $('#exportform').submit(); });	
	$('#xexport').click(function(){ $('#xhtml').val($('#amazing').html()); $('#xexportform').submit(); });
	function exportXLS() {
		$("#lol").text($("#amazing").html());
		$("#exceldata").text($("#lol").text());
		$("#excelcss").text($("#css").text());
		setTimeout(function() { $("#exportXLS").submit(); },500);
	}
	function exportPDF() {
		$("#lol").text($("#amazing").html());
		$("#pdfdata").text($("#lol").text());
		setTimeout(function() { $("#exportPDF").submit(); },500);
	}
	function exportDOC() {
		$("#lol").text($("#amazing").html());
		$("#docdata").text($("#lol").text());
		$("#htmlcss").text('qqq');
		setTimeout(function() { $("#exportDOC").submit(); },500);
	}
	function exportHTML() {
		$("#lol").text($("#amazing").html());
		$("#htmldata").text($("#lol").text());
		setTimeout(function() { $("#exportHTML").submit(); },500);
	}
	function exportCSV() { exportTableToCSV('Report'+<?php echo date("YmdHis"); ?>+'.csv') }
	function downloadCSV(csv, filename) {
		var csvFile;
		var downloadLink;
		csvFile = new Blob([csv], {type: "text/csv"});
		downloadLink = document.createElement("a");
		downloadLink.download = filename;
		downloadLink.href = window.URL.createObjectURL(csvFile);
		downloadLink.style.display = "none";
		document.body.appendChild(downloadLink);
		downloadLink.click();
	}
	function exportTableToCSV(filename) {
		var csv = []; var rows = document.querySelectorAll("table tr");
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
	function toCanvas(elemx){
		html2canvas(document.querySelector("#"+elemx)).then(canvas => {
		var b64 = canvas.toDataURL();
		$.ajax({  type: "POST",  url: "../mojo/imagesave", dataType: 'text', data: { base64data : b64 },
		success:function(d) { $("#"+elemx).html(d); } });  });
	}	
</script>

