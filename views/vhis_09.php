<body>
<div class="container-fluid" id="amazing">
<div id="xamazing">
<style type="text/css">
body { font-family:Arial !important; } table {border-spacing: 0;} th, td {padding: 0px;} .e{border: 1px solid #d7d7d7 }
</style>
<table class="table table-condensed" style="width:100%">
	<tr><th align='left' colspan="26" style="text-align:left;border-top:none;"><span style="font-family: Arial; font-size:11px!important;">Report Code: Vhis 09</span></th></tr>
	<tr style="text-align:center;font-family: Arial; font-size:16px!important;"><th align="center" colspan="26" style="border-top:none;">Outbound Voice - GP Customer Satisfaction Survey</th></tr>
	<tr ><th align="right" colspan="26" style="text-align:right;border-top:none;border-top:none;"><span style="font-family: Arial; font-size:11px!important;">Report Interval: 
	<?php 
	if($frequency=="Morning"){ echo date("m/d/Y 06:00:00 ",strtotime($datefrom))." - ". date("m/d/Y 14:00:00",strtotime($dateto));}
	elseif($frequency=="Afternoon"){ echo date("m/d/Y 14:00:00 ",strtotime($datefrom))." - ". date("m/d/Y 22:00:00",strtotime($dateto));}
	elseif($frequency=="Graveyard"){ echo date("m/d/Y 22:00:00 ",strtotime($datefrom))." - ". date("m/d/Y 06:00:00",strtotime($dateto)); }
	elseif($frequency=="Fifteen"){
		if(date("H:i",strtotime($dateto))!='23:59'){ echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y H:i:s",strtotime($dateto));}
		else{echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y 23:45:00",strtotime($dateto));}
	} elseif($frequency=="Thirty"){
		if(date("H:i",strtotime($dateto))!='23:59'){ echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y H:i:s",strtotime($dateto));} 
		else{ echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y 23:30:00",strtotime($dateto)); }
	} elseif($frequency=="Sixty"){
		if(date("H:i",strtotime($dateto))!='23:59'){ echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y H:i:s",strtotime($dateto));} 
		else{ echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y 23:00:00",strtotime($dateto));}
	}elseif($frequency=="W"){
		$day1 = date('w',strtotime($datefrom)); $day2 = date('w',strtotime($dateto)); echo date('m/d/Y H:i:s ', strtotime($datefrom .' -'.$day1.' days'))." - ". date("m/d/Y 23:59:59" ,strtotime($dateto .' +'.(6-$day2).' days'));
	} else{ echo date("m/d/Y H:i:s ",strtotime($datefrom))." - ". date("m/d/Y H:i:s",strtotime($dateto)); }
	?></span></th></tr></table><br/>
		<?php $sitearray = [];
			foreach($records->result_object() as $obj){
				switch($frequency){
					case "Fifteen": $dateInterval = $obj->{'Minus15Mins'}; break;
					case "Thirty": $dateInterval = $obj->{'Minus30Mins'}; break;
					case "Sixty": $dateInterval = $obj->{'Minus60Mins'}; break;
					case "D": $dateInterval = $obj->perDate; break;
					case "W": $dateInterval = $obj->Sunday; break;
					case "M": break;
					case "PM": $dateInterval = $obj->MONTH_NAME; break;
					case "Y": $dateInterval = $obj->YEAR; break;
					case "Morning": $dateInterval = $obj->Morning; break;
					case "Afternoon": $dateInterval = $obj->Afternoon; break;
					case "Graveyard": $dateInterval = $obj->Graveyard; break;
				}
				if($dateInterval != ""){ $data[$obj->SiteName][$dateInterval][] = $obj;
					if($obj->call_disposition == ""){ $ccd = "No Disposition"; }
					else{ $ccd = $obj->call_disposition; }
					$pieCallDisposition[$obj->SiteName][$ccd][] = $obj;
					if(!in_array($obj->SiteName,$sitearray)){ array_push($sitearray, $obj->SiteName); }
				}
			}
			foreach($sitearray as $siteIN){ 
			?>
			<table class="table table-condensed" style="width:100%;font-family: Helvetica; font-size:11px: border-spacing:0" width="100%" cellspacing=0 cellpadding=0>
						<tr style=""><td style="font-weight: bold;border: 1px solid #d7d7d7 ;background-color:#36CCF8" colspan="26">Site Name : <?php echo $siteIN; ?></td></tr>
						<tr style="background-color: #cacaca;">
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Interval</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">SIN</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Welcome Call Assignment Received Date</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Area</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Batch (Date) </td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Type of Application</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">RC / Customer Name</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">User Name or Popular Name</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Meralco Engineer </td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Time of Call</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Agent Name  </td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Service Provider</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Contact Person</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Telephone Number </td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Telephone Number 2 </td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Mobile Number 1</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Mobile Number 2 </td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Meralco Engineer Rating</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Service Application</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Feedback / Comments / Suggestions /Remarks </td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Person Contacted</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Date Surveyed</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Time Surveyed</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Call Disposition</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Date of Call Attempt</td>
							<td style="border: 1px solid #d7d7d7;font-family:Helvetica;text-align:center;font-size:11px;font-weight:bold">Time of Call Attempt </td>
						</tr>
			<?php		
				foreach($data[$siteIN] as $key => $value){
					$topData = 0;$rowcount = 0;
					foreach($value as $tableplot){ $rowcount++; }
					foreach($value as $tableplot){
						switch($frequency){
							case "Fifteen": $dateIntervalplot = $tableplot->{'Minus15Mins'}; $adder = " - ".date("H:i:s",strtotime($tableplot->{'Minus15Mins'} . " +15 minutes")); break;
							case "Thirty": $dateIntervalplot = $tableplot->{'Minus30Mins'}; $adder = " - ".date("H:i:s",strtotime($tableplot->{'Minus30Mins'} . " +30 minutes")); break;
							case "Sixty": $dateIntervalplot = $tableplot->{'Minus60Mins'}; $adder = " - ".date("H:i:s",strtotime($tableplot->{'Minus60Mins'} . " +60 minutes")); break;
							case "D": $dateIntervalplot = $tableplot->perDate; $adder = ""; break;
							case "W": $dateIntervalplot = $tableplot->Sunday; $adder =  " - ".date("m/d/Y",strtotime($tableplot->Saturday)); break;
							case "M": break;
							case "PM": $dateIntervalplot = $tableplot->MONTH_NAME; $adder = ""; break;
							case "Y": $dateIntervalplot = $tableplot->YEAR; $adder = ""; break;
							case "Morning": $dataexploder = explode(" - ",$tableplot->Morning); $dateIntervalplot = $tableplot->Morning; $datex = (date("m/d/Y H:i:s",strtotime( $dataexploder[0]))); $adder = " - ".date("m/d/Y H:i:s",strtotime( $datex ."+8 Hours")); break;
							case "Afternoon": $dataexploder = explode(" - ",$tableplot->Afternoon); $dateIntervalplot = $tableplot->Afternoon; $datex = (date("m/d/Y H:i:s",strtotime( $dataexploder[0]))); $adder = " - ".date("m/d/Y H:i:s",strtotime( $datex ."+8 Hours")); break;
							case "Graveyard": $dataexploder = explode(" - ",$tableplot->Graveyard); $dateIntervalplot = $tableplot->Graveyard; $datex = (date("m/d/Y H:i:s",strtotime( $dataexploder[0]))); $adder = " - ".date("m/d/Y H:i:s",strtotime( $datex ."+8 Hours")); break;
						}
						echo "<tr style='text-align:center;font-family: Helvetica;font-size: 11px;'>";

						if($key == $dateIntervalplot && $topData == 0){
							if($frequency == "Morning" ||$frequency == "Afternoon" ||$frequency == "Graveyard"){ echo "<td style='text-align:center;border-top: 1px solid #d7d7d7;border-left: 1px solid #d7d7d7;border-right: 1px solid #d7d7d7;border-bottom:1px solid #d7d7d7'>".$datex.$adder."</td>"; }
							elseif($frequency == "Fifteen" ||$frequency == "Thirty" ||$frequency == "Sixty"){ echo "<td style='text-align:center;border-top: 1px solid #d7d7d7;border-left: 1px solid #d7d7d7;border-right: 1px solid #d7d7d7;border-bottom:1px solid #d7d7d7'>".date("m/d/Y H:i:s",strtotime($key)).$adder."</td>"; }
							elseif($frequency == "D" ||$frequency == "W" ){ echo "<td style='text-align:center;border-top: 1px solid #d7d7d7;border-left: 1px solid #d7d7d7;border-right: 1px solid #d7d7d7;border-bottom:1px solid #d7d7d7'>".date("m/d/Y",strtotime($key)).$adder."</td>"; }
							else{ echo "<td style='text-align:center;border-top: 1px solid #d7d7d7;border-left: 1px solid #d7d7d7;border-right: 1px solid #d7d7d7;border-bottom:1px solid #d7d7d7'>".$key.$adder."</td>"; }
							$topData = 1;
						} else { echo "<td style='border-left: 1px solid #d7d7d7;border-right: 1px solid #d7d7d7;border-bottom:1px solid #d7d7d7'> </td>"; }
						?>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'SIN'}; ?><span style="color:#fff;">`</span></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'ReceivedDate'}; ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'Area'}; ?> </td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'BatDate'}; ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'TypeOfApp'}; ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'CustomerName'}; ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'username'}; ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'MerEng'}; ?> </td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo date('H:i:s', strtotime($tableplot->{'TimeOfCall'})); ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'agent_name'}; ?> </td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'ServiceProvider'}; ?> </td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'ContactPerson'}; ?> </td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'TelNumUsed'}; ?><span style="color:#fff;">`</span> </td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'TelNum2Used'}; ?><span style="color:#fff;">`</span> </td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'CellNumUsed'}; ?><span style="color:#fff;">`</span> </td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'CellNum2Used'}; ?><span style="color:#fff;">`</span></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'MerEngRating'}; ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'SerAppRating'}; ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'Suggestion'}; ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'PerCon'}; ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'DateSurveyed'}; ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'TimeSurveyed'}; ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo $tableplot->{'call_disposition'}; ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo date('m/d/Y', strtotime($tableplot->{'DateofCallAttempt'})); ?></td>
						<td style="border: 1px solid #d7d7d7;font-family:Helvetica;font-size:11px;text-align:center;vertical-align:middle;"><?php echo date('H:i:s', strtotime($tableplot->{'TimeOfCall'})); ?></td>
						<?php echo "</tr>";
					}
				}
				echo "<tr class='e' ><td style='padding:0px' colspan='26'></td></tr>";echo "</table>";	
			?>
			<table width="100%" style="border:none;height:330px;"><tr style="border:none;"><br/><td style="border:none;" rowspan="17"><div id='chartdiv<?php echo preg_replace('/\s+/', '_', $siteIN); ?>' style="width:600px; height:300px;margin:0 auto;"></div></td></tr></table>

		<?php } ?>
		<br><table width="100%" ><tr><td colspan="26" style="border:none"><?php echo "<span style='font-family:Helvetica;font-size:11px;'>Printed By: " . ($this->input->post('fullname') != "" ? $this->input->post('fullname'):$this->session->userdata('Fullname'))  . ": " .date('m/d/Y H:i:s') . '</span>'; ?></td></tr></table>
		</div></div>
	<div class="content" style="<?php  echo ($this->input->post("format") <> "" ? "display: none;" : "" ); ?>"><h3>Export:</h3>
		<div style="float:left; padding-right:10px;">
			<input type="button" class="btn btn-success" onclick="exportPDF();" value="PDF">
			<form id="exportPDF" action="<?php echo base_url("index.php/report_controller_z/generatePDF"); ?>" method="post" target="_blank">
				<textarea id="PDFdata" name="html" style="display:none"></textarea><input type="hidden" name="orient" value="1"><input type="hidden" name="thin" value="2">
			</form>
		</div>
		<div style="float:left; padding-right:10px;"><input type="button" class="btn btn-success" onclick="exportXLS()" value="EXCEL">
			<form id="exportXLS" action="<?php echo base_url("index.php/report_controller_z/excel"); ?>" method="post" target="_blank">
				<textarea id="exceldata" name="xhtml" style="display:none"></textarea><textarea id="excelcss" name="css" style="display:none"></textarea>
			</form>
		</div>		
		<div style="float:left; padding-right:10px;"><input type="button" class="btn btn-success" onclick="exportCSV()" value="CSV"></div>
		<div style="float:left; padding-right:10px;"><input type="button" class="btn btn-success" onclick="exportDOC();" value="WORD">
			<form id="exportDOC" action="../report_controller_z/generateDOC"" method="post" target="_blank">
				<textarea id="docdata" name="html" style="display:none"></textarea><textarea id="htmlcss" name="css" style="display:none">gg</textarea><input type="hidden" name="orient" value="1">
			</form>
		</div>
		<div style="float:left; padding-right:10px;"><input type="button" class="btn btn-success" onclick="exportHTML();" value="HTML">
			<form id="exportHTML" action="../report_controller_z/generateHTML" method="post" target="_blank"><textarea id="htmldata" name="html" style="display:none"></textarea></form>
		</div>
			<form id="exportXLSServer" action="<?php echo base_url("index.php/report_controller_z/generateXLSServer"); ?>" method="post">
				<textarea id="exceldataserver" name="html" style="display:none"></textarea><textarea id="excelcssserver" name="css" style="display:none"></textarea>
			</form>
			<form id="exportPDFServer" action="<?php echo base_url("index.php/report_controller_z/generatePDFServer"); ?>" method="post">
				<textarea id="PDFdataserver" name="html" style="display:none"></textarea>
			</form>
			<form id="exportDOCServer" action="<?php echo base_url("index.php/report_controller_z/generateDOCServer"); ?>" method="post">
				<textarea id="docdataserver" name="html" style="display:none"></textarea><textarea id="htmlcssserver" name="css" style="display:none"></textarea>
			</form>
			<form id="exportHTMLServer" action="<?php echo base_url("index.php/report_controller_z/generateHTMLServer"); ?>" method="post">
				<textarea id="htmldataserver" name="html" style="display:none"></textarea>
			</form>
			<form id="exportCSVServer" action="<?php echo base_url("index.php/report_controller_z/generateCSVServer"); ?>" method="post">
				<textarea id="csvdataserver" name="html" style="display:none"></textarea>
			</form>
		<div id="lol" style="display:none;"></div>
	</div></body>
<script src="<?php echo base_url(); ?>assets/js/html2canvas.min.js"></script> 
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jsPDF.debug.js"></script>
<script type="text/javascript">
$(document).ready(function() {
<?php foreach($sitearray as $siteIN){
		$calldispochart = "";$total = 0;
		foreach($pieCallDisposition[$siteIN] as $keycd => $valcd){
			foreach($valcd as $cd){$total++;}
		}
		foreach($pieCallDisposition[$siteIN] as $keycd => $valcd){
			$count = 0;
			foreach($valcd as $cd){ $count++; }
			if($total == 0){$per = '0%';}else{$per = round(($count/$total)*100 , 2).'%';}
			$calldispochart .= '{ "label": "'. '  '. $keycd .' : ' . $count .' - '.$per.' ", "data": "'.$count.'"}, ';
		}
		$calldispochart = rtrim($calldispochart,",");
?>
	var data = [ <?php echo $calldispochart; ?> ];
    $.plot('#chartdiv<?php echo preg_replace('/\s+/', '_', $siteIN); ?>', data, {
    	series: { pie: { show: true, label: { show: true, radius: 20/20, threshold: 2,
                    formatter: function (label, series) { var element = '<div style="font-size:8pt; text-align:center;padding:2px;">' + label + '<br/>' + series.data[0][1] + '</div>'; return element; }
                } } },
        legend: { show: true,margin:20 }
	});
	toCanvas('chartdiv<?php echo preg_replace('/\s+/', '_', $siteIN); ?>');
<?php  } ?>		
		<?php if($this->input->post('scheduled') == true): ?>
		<?php if($this->input->post('format') == "PDF"): ?>
			setTimeout(function() {
				$("#lol").text($("#amazing").html());$("#PDFdataserver").text($("#lol").text());$("#exportPDFServer").submit();
			},35000);
		<?php elseif($this->input->post('format') == "WORD"): ?>
			setTimeout(function() {
				$("#lol").text($("#amazing").html());$("#docdataserver").text($("#lol").text());$("#htmlcssserver").text($("#css").text());$("#exportDOCServer").submit();
			},35000);
		<?php elseif($this->input->post('format') == "HTML"): ?>
			setTimeout(function() {
				$("#lol").text($("#amazing").html());$("#htmldataserver").text($("#lol").text());$("#exportHTMLServer").submit();
			},35000);
		<?php elseif($this->input->post('format') == "CSV"): ?>
			var csv = [];
			var rows = document.querySelectorAll("table tr");
			for (var i = 0; i < rows.length; i++) {
				var row = [], cols = rows[i].querySelectorAll("td, th");
				for (var j = 0; j < cols.length; j++) 
					row.push(cols[j].innerText);
					csv.push(row.join(","));        
			}
			var finaldata = csv.join("\n");
			$("#csvdataserver").text(finaldata);
			setTimeout(function() { $("#exportCSVServer").submit(); },35000);
		<?php else: ?>
			setTimeout(function() { $("#lol").text($("#amazing").html());$("#exceldataserver").text($("#lol").text());$("#excelcssserver").text($("#css").text());$("#exportXLSServer").submit(); },35000);
		<?php endif; ?>
		<?php endif; ?>
});
	$('#export').click(function(){ $('#html').val($('#amazing').html());$('#exportform').submit(); });
	$('#xexport').click(function(){$('#xhtml').val($('#amazing').html());$('#xexportform').submit();});
	function exportXLS() {
		$("#lol").text($("#amazing").html());$("#exceldata").text($("#lol").text());$("#excelcss").text($("#css").text());
		setTimeout(function() { $("#exportXLS").submit(); },500);
	}
	function exportPDF() {
		$("#lol").text($("#amazing").html());$("#PDFdata").text($("#lol").text());
		setTimeout(function() { $("#exportPDF").submit(); },500);
	}
	function exportDOC() {
		$("#lol").text($("#amazing").html());$("#docdata").text($("#lol").text());$("#htmlcss").text('qqq');
		setTimeout(function() { $("#exportDOC").submit(); },500);
	}
	function exportHTML() {
		$("#lol").text($("#amazing").html()); $("#htmldata").text($("#lol").text());
		setTimeout(function() { $("#exportHTML").submit(); },500);
	}
	function exportCSV() {
		exportTableToCSV('Report'+<?php echo date("YmdHis"); ?>+'.csv')
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
	function exportTableToCSV(filename) {
		var csv = [];
		var rows = document.querySelectorAll("table tr");
		for (var i = 0; i < rows.length; i++) {
			var row = [], cols = rows[i].querySelectorAll("td, th");
			for (var j = 0; j < cols.length; j++) {
				var str = cols[j].innerText;
				if (str.includes('`')){ row.push('="' + cols[j].innerText.replace("`","") + '"'); }
				else if (str.includes(',')){ row.push('="' + cols[j].innerText.replace(/,/g," ") + '"'); }
				else{ row.push('="' + cols[j].innerText + '"'); }
			}
			csv.push(row.join(","));        
		}
		downloadCSV(csv.join("\n"), filename);
	}
	function toCanvas(elemx){
		html2canvas(document.querySelector("#"+elemx)).then(canvas => {
			var b64 = canvas.toDataURL();
			$.ajax({ type: "POST", url: "../mojo/imagesave", dataType: 'text',
				data: { base64data : b64 },
				success:function(d) { $("#"+elemx).html(d); }
			}); 
		});
	}	
</script>

