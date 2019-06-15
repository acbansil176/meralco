<?php

if (!defined('BASEPATH'))
exit('No direct script access allowed');

class vhisSaso_model extends CI_Model {
	
	public function vhis_23($dateFrom, $dateTo, $atd,$tlx){
		$cmd = "EXEC [dbo].[Get_Vhis22] @FromDateTime = '".trim($dateFrom)."', @ToDateTime = '".trim($dateTo)."', @Attendant_Ids = '".$atd."', @telex = '" . $tlx . "'";
		$query = $this->db->query($cmd);
		return $query;
	}
	
	public function vhis_22($dateFrom, $dateTo ,$atd,$tlx){
		$cmd = "EXEC [dbo].[Get_Vhis22] @FromDateTime = '".trim($dateFrom)."', @ToDateTime = '".trim($dateTo)."', @Attendant_Ids = '".$atd."', @telex = '" . $tlx . "'";
		$query = $this->db->query($cmd);
		return $query;
	}	
	
	public function vhis_6B($dateFrom, $dateTo, $trunks){
		$trunkids = rtrim($trunks,",");
		$temp = explode(",",$trunkids);
		$trunkids = "'" . implode ( "', '", $temp ) . "'";
		$cmd = "declare @FromDateTime datetime,@ToDateTime datetime, @Trunk_Ids nvarchar(50); set @FromDateTime = '".trim($dateFrom)."';set @ToDateTime = '".trim($dateTo)."' ;
			with periodData as ( select 	* from REPDB..CiscoCDR 	where  DATEADD(HOUR, 8, DATEADD(SECOND,CONVERT(BIGINT,dateTimeOrigination),'19700101'))   >= @FromDateTime AND  DATEADD(HOUR, 8, DATEADD(SECOND,CONVERT(BIGINT,dateTimeOrigination),'19700101'))  <= @ToDateTime AND  origDeviceName IN (".$trunkids.")	),
			table1 as (	select	origDeviceName,SUM(1) as cnt,CONVERT(varchar(35),DATEADD(SECOND,CONVERT(BIGINT,dateTimeOrigination),'19700101') ,25)as dateTimeOrigination	from periodData	group by origDeviceName,dateTimeOrigination	),
			table2 as (	select table1Derived.cnt,table1Derived.origDeviceName from table1  table1Derived left join table1 table2 on table1Derived.origDeviceName = table2.origDeviceName and table1Derived.cnt < table2.cnt
				 where table2.cnt is null group by table1Derived.origDeviceName,table1Derived.cnt	),
			table4 as (	select distinct origDeviceName, 5 as \"Lines Required\" from periodData 	),
			result as (	select table2.*, (select top 1 dateTimeOrigination from table1 where origDeviceName = table2.origDeviceName and cnt = table2.cnt order by dateTimeOrigination desc) as PeakDate from table2	)
			,result2 as ( select  tbl1.origDeviceName,Capacity,CallVolumeAttempt as \"Call Volume Attempt\",CallVolumeBlocked as \"Call Volume Blocked\",	Cast(Round((TotalDuration*1.0)/60/60,2) as decimal(18,2)) as \"Total Duration (in hrs)\"		from (select  origDevicename,30 as Capacity,count(globalCallID_callId) as CallVolumeAttempt, sum(duration) as TotalDuration, SUM(CASE WHEN dateTimeConnect = 0 THEN 1 ELSE 0 END)  as CallVolumeBlocked from periodData group by origDeviceName) as tbl1	),
			result3 as (select  tbl2.origDeviceName, CallVolumeCompleted as \"Call Volume Completed\", Round((Cast(AverageDuration*1.0 as decimal(18,2))/60),2) as \"Average Duration (in mins)\"
					from (select  origDevicename,AVG(CAST(duration as decimal(18,2))) as AverageDuration,SUM(CASE WHEN dateTimeConnect <> 0 THEN 1 ELSE 0 END) as CallVolumeCompleted from periodData where dateTimeConnect <> 0
					group by origDeviceName) as tbl2),
			atb as (select Table1.origDevicename,Max(Table1.MaxUsed) as SumMaxUsed, SUM(CASE WHEN Table1.MaxUsed >= Capacity THEN 1 ELSE 0 END) as ATB,  AVG(CAST(Table1.MaxUsed AS DECIMAL(10,2))) as AvgMaxUsed  from
				(select  origDevicename,count(*) as MaxUsed,30 as Capacity,DATEADD(MINUTE,DATEDIFF(minute,0,DATEADD(SECOND,CONVERT(BIGINT,dateTimeOrigination),'19700101') )/60*60,0)  as dateTimeOrigination
					from periodData	group by origDeviceName, dateTimeOrigination) Table1 group by origDeviceName)
			select result2.origDeviceName,result2.Capacity,atb.SumMaxUsed as \"Max Used\",atb.AvgMaxUsed as \"Average Used\",Cast(Round((atb.SumMaxUsed*1.0)/(Capacity*1.0)*100,2) as decimal(18,2)) as \"% Utilized (Peak)\",
			Cast(Round((atb.AvgMaxUsed*1.0)/(Capacity*1.0)*100,2) as decimal(18,2)) as \"% Utilized (Period)\",	Cast(Round((result2.[Total Duration (in hrs)]*60.0)/3600,2) as decimal(18,2)) as \"Traffic Intensity\",
			table4.[Lines Required],Capacity-table4.[Lines Required] as Variance,FORMAT(convert(date,PeakDate,102), 'dd-MM-yyyy') as \"Peak Period (Date)\",
			REPLACE(SUBSTRING(CONVERT(nvarchar(12), CAST(convert(time,DATEADD(HOUR, 8,PeakDate)) AS time(1)), 114), 0, 6) + ':', ':', '') as \"Peak Period (Time)\", 
			result2.[Call Volume Attempt],result3.[Call Volume Completed],result2.[Call Volume Blocked],result3.[Average Duration (in mins)],result2.[Total Duration (in hrs)],
			atb.ATB	from result left join result2 on result.origDeviceName = result2.origDeviceName	left join table4 on result.origDeviceName = table4.origDeviceName
			left join atb on result.origDeviceName = atb.origDeviceName	left join result3 on result.origDeviceName = result3.origDeviceName 
			UNION ALL select 'Summary' as origDeviceName,sum(Capacity) as Capacity,	SUM(atb.SumMaxUsed) as 'Max Used',avg(atb.AvgMaxUsed) as 'Average Used',
			Cast(Round((sum(atb.SumMaxUsed)*1.0)/(sum(Capacity)*1.0)*100,2) as decimal(18,2)) as '% Utilized (Peak)',
			Cast(Round((avg(atb.AvgMaxUsed)*1.0)/(sum(Capacity)*1.0)*100,2) as decimal(18,2)) as '% Utilized (Period)',
			Cast(Round((sum(result2.[Total Duration (in hrs)])*60.0)/3600,2) as decimal(18,2)) as 'Traffic Intensity',
			sum(table4.[Lines Required]),sum(Capacity-table4.[Lines Required]) as Variance,	FORMAT(convert(date,max(PeakDate),102), 'dd-MM-yyyy') as 'Peak Period (Date)',
			REPLACE(SUBSTRING(CONVERT(nvarchar(12), CAST(convert(time,DATEADD(HOUR, 8,max(PeakDate))) AS time(1)), 114), 0, 6) + ':', ':', '') as 'Peak Period (Time)', 
			sum(result2.[Call Volume Attempt]),	sum(result3.[Call Volume Completed]),sum(result2.[Call Volume Blocked]),
			case when sum( (result2.[Total Duration (in hrs)] *60)  / result3.[Average Duration (in mins)] ) = 0 then  sum(result3.[Average Duration (in mins)]) else (sum(result2.[Total Duration (in hrs)]) * 60) / sum( (result2.[Total Duration (in hrs)] *60)  / result3.[Average Duration (in mins)] ) end as test,
			sum(result2.[Total Duration (in hrs)]),	sum(atb.ATB) from result left join result2 on result.origDeviceName = result2.origDeviceName left join table4 on result.origDeviceName = table4.origDeviceName
			left join atb on result.origDeviceName = atb.origDeviceName	left join result3 on result.origDeviceName = result3.origDeviceName group by Capacity";
	$query = $this->db->query($cmd);
	return $query;
	}
	
	public function get_vhis11_data($datefrom,$dateto,$siteid){
	
		$siteid = rtrim($siteid,",");
		$temp = explode(",",$siteid);
		$siteid = "'" . implode ( "', '", $temp ) . "'";
		$cmd = "select 
		   DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 15, DATEADD(HOUR, 8, AOD.[CallStartDt]))) / 15 * 15, 0) as 'Interval',
			DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Aod.CallStartDt)) / 15 * 15, 0)) as '15MinsInterval',
			DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Aod.CallStartDt)) / 30 * 30, 0)) as '30MinsInterval',
			DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Aod.CallStartDt)) / 60 * 60, 0)) as '60MinsInterval',
			CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8,aod.callstartdt))) %7 , dateadd(hour, 8,aod.callstartdt))), 101) as Sunday,
			CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8,aod.CallStartDt))) %7 , dateadd(hour, 8,aod.callstartdt))), 101) as Saturday,
			convert(varchar(10), dateadd(hour, 8,Aod.CallStartDt), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Aod.CallStartDt)) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Minus15Mins,
			convert(varchar(10), dateadd(hour, 8,Aod.CallStartDt), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Aod.CallStartDt)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Minus30Mins,
			convert(varchar(10), dateadd(hour, 8,Aod.CallStartDt), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, Aod.CallStartDt)) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Minus60Mins,
			datepart(wk, dateadd(hour, 8, Aod.CallStartDt)) as Week_count,datepart(w, dateadd(hour, 8, Aod.CallStartDt)) as Day_Count, 
			datename(dw, dateadd(hour, 8, Aod.CallStartDt)) as Day_Name,convert(varchar(10), dateadd(hour, 8,Aod.CallStartDt), 101) as perDate,	DATEPART(M, DATEADD(HOUR, 8, Aod.CallStartDt)) AS Month_Count,
			cast(datename(m, dateadd(hour,8,Aod.CallStartDt)) as varchar(10)) + ', ' + cast(year(dateadd(hour,8,Aod.CallStartDt)) as varchar(10)) AS MONTH_NAME,
			cast(year(dateadd(hour,8,Aod.CallStartDt)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,8,Aod.CallStartDt)) as varchar(10)) as Year_Month,
			YEAR(DATEADD(HOUR, 8, Aod.CallStartDt)) AS YEAR,DATEADD(HOUR, 8, Aod.CallStartDt) AS DATE_TIME,	convert(varchar(10), dateadd(hour, 8,aod.callstartDt), 101) +' '+
			case when datepart(hour, dateadd(hour, 8, aod.callstartdt)) <= 5 or datepart(hour, dateadd(hour, 8, aod.callstartdt)) >= 22 then '10PM - 6AM' end as Graveyard,
			convert(varchar(10), dateadd(hour, 8,aod.callstartDt), 101) +' '+
			case when datepart(hour, dateadd(hour, 8, aod.callstartdt)) > = 14 and datepart(hour, dateadd(hour, 8, aod.callstartdt)) <= 21 then '2PM - 10PM'  end as Afternoon,
			convert(varchar(10), dateadd(hour, 8,aod.callstartDt), 101) +' '+
			 case when datepart(hour, dateadd(hour, 8, aod.callstartdt)) >= 6 and datepart(hour, dateadd(hour, 8, aod.callstartdt)) <= 13 then '6AM - 2PM' end as Morning, 
			G.SiteName as SiteName,	x.SIN as SINumber,x.PPIType,x.TimeCategory,	x.CustomerAdvisoryNo,x.ProvinceCity,x.DateOfInterruption,cast(x.TimeOfInterruption  as time(0)) AS TimeOfInterruption,x.EndDateOfInterr,
			cast(x.EndTimeOfInter  as time(0)) as EndTimeOfInter,x.CircuitNumber,x.AssignedEngineer,x.DateEndoOfServProv,x.ServiceProvider,x.CustomerName,x.TelephoneNo,x.CellphoneNo,x.DateAdvised,x.TimeAdvised,x.Remarks,
			x.Landline1,x.Cellphone1,x.Email1,x.Landline2,x.Cellphone2,x.Email2,x.RespondentsName,x.PopularName,x.ServiceAddress,x.Call1_Phone,	DATEDIFF(DAY,x.DateEndoOfServProv,x.DateAdvised) as Date_Processed,
			DATEADD(HOUR, 8, AOD.[CallStartDt]) as [CallStartDt],AOD.[SeqNum] as [SeqNum],AOD.[CallId] as [CallId],AOD.[CallTypeId] as [CallTypeId],
			AOD.[CallCategoryId] as [CallCategoryId],AOD.[CallActionId] as [CallActionId],AOD.[CallActionReasonId] as [CallActionReasonId],	AOD.[Service_Id] as [Service_Id],AOD.[Table_Id] as [Table_Id],
			AOD.[RecordNum] as [Record_Num],AOD.[User_Id] as [User_Id],(SELECT [UserFullName] FROM [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Users] WHERE [User_Id] = AOD.[User_Id]) as [AgentName],
			CD.[DialedNum] as [DialedNum],CD.[Station] as [Station],	CASE WHEN AOD.[AgentDispId] IS NOT NULL THEN AOD.[AgentDispId] ELSE AOD.[SwitchDispId] END as [DispositionId],
			(SELECT [Disposition_desc] FROM [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Disposition] WHERE [Disp_Id] = CASE WHEN AOD.[AgentDispId] IS NOT NULL THEN AOD.[AgentDispId] ELSE AOD.[SwitchDispId] END) as [Disposition_desc],x.Reason,
			DATEADD(HOUR, 8,  x.Call1_dt) as Call1_dt,x.Disp1_Id,(SELECT [Disposition_desc] FROM [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Disposition] WHERE [Disp_Id] = x.Disp1_Id) as [Disp1_desc]
			FROM [REPDB].[dbo].[AODCallDetail] AOD,	[REPDB].[dbo].[CallDetail] CD,	RepUIDB.dbo.Stations f,	 RepUIDB.dbo.Sites g,
			[OUTDB].[dbo].[Outbound_Report11] x	WHERE  DATEADD(HOUR, 8, AOD.[CallStartDt]) BETWEEN '" . $datefrom ."' and '" . $dateto ."'  and
			AOD.[CallStartDt] = CD.[CallStartDt] AND AOD.[SeqNum] = CD.[SeqNum] AND AOD.[CallId] = CD.[CallId] and AOD.[CallTypeId] = 2 AND AOD.[Table_Id] <> -5 
			and cd.Station = f.Station and f.SiteGuid = g.SiteGuid and x.Table_Id = aod.Table_Id and aod.RecordNum= x.Record_Num and G.SiteName in (". $siteid .") ORDER BY AOD.[CallStartDt] ASC";
		$query = $this->db->query($cmd);
	    $output = $query->result();
		return $query;
	}
	
	public function get_vhis30_data($datefrom,$dateto,$siteid,$skillid,$serviceid){
		
	$siteid = rtrim($siteid,",");
	$temp = explode(",",$siteid);
	$sid = "'" . implode ( "', '", $temp ) . "'";
	$skd = rtrim($skillid,",");
	$svd = rtrim($serviceid,",");
		
		$cmd ="with base_table_vhis28 as (select DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, a.CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval
			,c.Service_c,dateadd(hour, 8, a.CallStartDt) as CallStartDt,a.Service_Id,a.Skill_Id,a.SeqNum,d.User_Id
			,b.Skill_Desc,a.AgentSkillLevel,st.SiteName,dateadd(hour, 8, d.QueueStartDt) as QueueStartDt
			,dateadd(hour, 8 ,d.QueueEndDt) as QueueEndDt,dateadd(hour, 8 ,d.WrapEndDt) as WrapEndDt,d.CallActionId
			from repdb.dbo.ASBRCallSkillDetail a left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Skills] b	on a.Skill_Id = b.Skill_Id
			left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].service c on a.service_id = c.Service_Id left join repdb.dbo.ACDCallDetail d on a.SeqNum = d.SeqNum and a.CallId = d.CallId
			left join RepUIDB.dbo.Stations s on d.Station = s.Station left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid where a.skill_id not in (4000001,4000002)	and CallTypeId = 1 )
		, base_agent_Count as (
			select distinct	dateadd(hour,8,a.callstartdt) as CallStartDt,dateadd(hour,8,b.logindt) as LoginDt,dateadd(hour,8,b.logoutdt) as LogoutDt,b.User_Id as Online,a.Service_Id,aa.Station,aa.ModifiedDt,st.SiteName
			,a.CallActionId,m.Param15,b.User_Id,asbr.AgentSkillLevel from repdb.dbo.ACDCallDetail a left join repdb.dbo.AgentLoginLogout b
			on a.Service_Id = b.Service_Id and dateadd(hour,8,a.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt)
			left join	repdb.dbo.MediaDataDetail m	on b.Service_Id = m.Service_Id	and dateadd(hour,8,a.callstartdt) = dateadd(hour,8,m.callstartdt)
			and m.SeqNum = a.SeqNum and m.CallId = a.CallId	left join repdb.dbo.AgentStateAudit aa	on b.User_Id = aa.User_Id and DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,b.LoginDt)) / 1 * 1, 0)) 
			= DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,aa.ModifiedDt)) / 1 * 1, 0)) 
			left join RepUIDB.dbo.Stations s on aa.Station = s.Station	left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid	and aa.Agent_Index is not null
			left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.Service_Id = a.Service_Id
			left join repdb.dbo.ASBRCallSkillDetail asbr on asbr.SeqNum = a.SeqNum	and asbr.CallId = a.CallId	and asbr.CallStartDt = a.CallStartDt
			where a.Service_Id IN (4000013,4000019,4000021,4000023,4000025, 4000027, 4000029) and a.CallActionId in (5, 6, 18)	and asbr.AgentSkillLevel = 0 )
		, table_2 as (
			select distinct	dateadd(hour,8,a.callstartdt) as CallStartDt,dateadd(hour,8,b.logindt) as LoginDt,dateadd(hour,8,b.logoutdt) as LogoutDt,b.User_Id as Online,a.Service_Id,a.CallActionId,m.Param15,st.SiteName as DNIS_SiteTagging
			from repdb.dbo.ACDCallDetail a	left join repdb.dbo.AgentLoginLogout b on a.Service_Id = b.Service_Id and dateadd(hour,8,a.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt)
			left join repdb.dbo.MediaDataDetail m	on m.CallStartDt = a.CallStartDt left join RepUIDB.dbo.DNIS D on d.DNIS = m.Param15 left join RepUIDB.dbo.Sites st on d.SiteGuid = st.SiteGuid
			left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.Service_Id = a.Service_Id where a.Service_Id IN (4000013,4000019,4000021,4000023,4000025, 4000027, 4000029) and a.CallActionId in (5, 6, 18))
		, online_agent_perStation as (
			select distinct	DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, callstartDt)) / 15 * 15, 0) as FifteenMinuteInterval,CallStartDt,count(distinct Online) as Count,SiteName as StationOnline,Service_Id,CallActionId
			,Param15,AgentSkillLevel fromm base_agent_Count
			group by DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, callstartDt)) / 15 * 15, 0),SiteName,Service_Id,CallActionId,Param15,CallStartDt,AgentSkillLevel)
		, DNIS_tagging as ( select distinct	DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, callstartDt)) / 15 * 15, 0) as FifteenMinuteInterval,DNIS_SiteTagging as DNIS_Site,Service_Id,CallActionId,Param15 From table_2 )
		, final_query_abandoned as ( select distinct	a.FifteenMinuteInterval	,a.Count as AgentLoginCount,a.Service_Id,a.CallActionId,a.StationOnline,a.callstartdt,b.DNIS_Site,case when a.Count = 0 then 'MOC' when a.StationOnline != b.DNIS_Site then 'MOC' else b.DNIS_Site end as SiteName,a.AgentSkillLevel	from online_agent_perStation a left join DNIS_tagging b on a.Service_Id = b.Service_Id	and a.FifteenMinuteInterval = b.FifteenMinuteInterval and a.Param15 = b.Param15)
		, base_online_agents_perInterval as ( select FifteenMinuteInterval,count(distinct user_id) as Online,AgentSkillLevel,Service_Id,Skill_Id,SiteName	from base_table_vhis28 group by AgentSkillLevel,FifteenMinuteInterval,Service_Id,Skill_Id,SiteName)
		, novice_online as ( select FifteenMinuteInterval,Online as Online_Novice,AgentSkillLevel,Service_Id,Skill_Id from base_online_agents_perInterval where AgentSkillLevel = 2 )
		, devExpert_online as ( select	FifteenMinuteInterval,Online as Online_DevExpert,AgentSkillLevel,Service_Id,Skill_Id from base_online_agents_perInterval where AgentSkillLevel = 6 )
		, expert_online as (select	FifteenMinuteInterval,Online as Online_Expert,AgentSkillLevel,Service_Id,Skill_Id from	base_online_agents_perInterval where AgentSkillLevel = 10 )
		, calls_offered_table as ( select count(distinct seqNum) as Offered,FifteenMinuteInterval,CallStartDt,Service_Id,service_c,AgentSkillLevel,Skill_Id,Skill_Desc,CallActionId,siteName,user_id from base_table_vhis28 group by FifteenMinuteInterval,Service_Id,AgentSkillLevel,Skill_Id,Skill_Desc,CallActionId,CallStartDt,service_c,siteName,user_id )
		, expert_offered as (select	Offered,FifteenMinuteInterval,CallStartDt,Service_Id,service_c,AgentSkillLevel,Skill_Id,Skill_Desc,CallActionId,siteName,user_id from calls_offered_table where AgentSkillLevel IN (0,10))
		, calls_answered_table_Group as (select	FifteenMinuteInterval,CallStartDt,count(distinct seqNum) as Answered,datediff(second, queueStartDt, queueEndDt) as AnswerDelay,datediff(second, QueueEndDt, WrapEndDt) as HandlingTime
			,user_id,QueueStartDt,QueueEndDt,WrapEndDt,Service_Id,AgentSkillLevel,Skill_Id,CallActionId,service_c,Skill_Desc,SiteName from base_table_vhis28 where AgentSkillLevel != 0
			group by FifteenMinuteInterval,user_id,Service_Id,AgentSkillLevel,Skill_Id,SiteName,Skill_Desc,CallActionId,CallStartDt,QueueStartDt,QueueEndDt,service_c,Skill_Desc,WrapEndDt)
		, calls_offered_group as (select FifteenMinuteInterval,sum(Offered) as Offered,AgentSkillLevel,Service_Id,Service_c,Skill_Id,Skill_Desc,siteName,CallStartDt,user_id from calls_offered_table
			group by FifteenMinuteInterval,AgentSkillLevel,Service_Id,Service_c,Skill_Id,Skill_Desc,siteName,user_id,CallStartDt)
		, answered_group as ( select	FifteenMinuteInterval,Answered,sum(AnswerDelay) as AnswerDelay_Group,sum(HandlingTime) as HandlingTime_Group,sum(case when AnswerDelay <= 20 then 1 else 0 end) as WithinThreshold
			,AgentSkillLevel,user_id,Service_Id,Service_c,Skill_Id,Skill_Desc,SiteName,CallStartDt from	calls_answered_table_Group group by FifteenMinuteInterval,AgentSkillLevel,Service_Id,Service_c,SiteName,Skill_Id,Skill_Desc,user_id,Answered,CallStartDt )
		, answered_Group_With_Avg as ( select	FifteenMinuteInterval,Answered,AnswerDelay_Group as ASA_Group,HandlingTime_Group as AHT_Group,WithinThreshold,AgentSkillLevel,Service_Id,Service_c,Skill_Id,Skill_Desc,SiteName,User_Id,CallStartDt	from answered_group)
		, calls_answered_with_Novice as ( select distinct	FifteenMinuteInterval,Answered,AnswerDelay as AnswerDelay_Novice,HandlingTime as HandlingTime_Novice,AgentSkillLevel,Service_Id,Service_c,Skill_Id,Skill_Desc,User_Id,SiteName,CallStartDt from	calls_answered_table_Group where AgentSkillLevel = 2)
		, calls_answered_with_DevExpert as ( select distinct	FifteenMinuteInterval,Answered,AnswerDelay as AnswerDelay_DevExpert,HandlingTime as HandlingTime_DevExpert,AgentSkillLevel,Service_Id,Service_c,Skill_Id,Skill_Desc,User_Id,SiteName,CallStartDt from calls_answered_table_Group where AgentSkillLevel = 6),
		calls_answered_with_Expert as ( select distinct	FifteenMinuteInterval,Answered,AnswerDelay as AnswerDelay_Expert,HandlingTime as HandlingTime_Expert,AgentSkillLevel,Service_Id,Service_c,Skill_Id,Skill_Desc,User_Id,SiteName,CallStartDt	from calls_answered_table_Group	where AgentSkillLevel = 10), 
		novice_group as (select FifteenMinuteInterval,sum(Answered) as Answered,sum(AnswerDelay_Novice) as AnswerDelay_Novice,sum(HandlingTime_Novice) as HandlingTime_Novice,AgentSkillLevel,Service_Id,service_c,Skill_Id,Skill_Desc,User_Id ,SiteName,CallStartDt from calls_answered_with_Novice group by FifteenMinuteInterval,AgentSkillLevel,Service_Id,service_c,Skill_Id,Skill_Desc,SiteName,User_Id,CallStartDt )
		, novice_group_with_AVG as ( select FifteenMinuteInterval,Answered,AnswerDelay_Novice as ASA_Novice,HandlingTime_Novice as AHT_Novice,AgentSkillLevel,Service_Id,Service_c,Skill_Id,Skill_Desc,User_Id,SiteName,CallStartDt from novice_group ), 
		devExpert_group as ( select FifteenMinuteInterval,sum(Answered) as Answered,sum(AnswerDelay_DevExpert) as AnswerDelay_DevExpert,sum(HandlingTime_DevExpert) as HandlingTime_DevExpert,AgentSkillLevel,Service_Id,service_c,Skill_Id,Skill_Desc
			,User_Id,SiteName,callstartdt from 	calls_answered_with_DevExpert group by FifteenMinuteInterval,AgentSkillLevel,Service_Id,service_c,SiteName,Skill_Id,User_Id,Skill_Desc,callstartdt )
		, devExpert_group_with_AVG as (
			select FifteenMinuteInterval,Answered,AnswerDelay_DevExpert as ASA_DevExpert,HandlingTime_DevExpert as AHT_DevExpert,AgentSkillLevel,Service_Id,Service_c,Skill_Id,Skill_Desc,User_Id,SiteName,callstartDt from devExpert_group
		)

		, expert_group as (
			select 	FifteenMinuteInterval,sum(Answered) as Answered,sum(AnswerDelay_Expert) as AnswerDelay_Expert,sum(HandlingTime_Expert) as HandlingTime_Expert,AgentSkillLevel,Service_Id,service_c,Skill_Id,Skill_Desc,SiteName
			,User_Id,CallStartDt from calls_answered_with_Expert group by FifteenMinuteInterval,AgentSkillLevel,SiteName,Service_Id,service_c,Skill_Id,Skill_Desc,User_Id,CallStartDt
		), 
		
		Expert_group_with_AVG as (
			select	FifteenMinuteInterval,Answered,AnswerDelay_Expert as ASA_Expert	,HandlingTime_Expert as AHT_Expert,AgentSkillLevel,Service_Id,SiteName ,Service_c,Skill_Id,CallStartDt,Skill_Desc,User_Id from	Expert_group
		)

		, base_AgentLogin_Count as (
			select distinct	dateadd(hour, 8 ,a.callstartdt) as CallStartDt,dateadd(hour,8,b.logindt) as LoginDt,dateadd(hour,8,b.logoutdt) as LogoutDt,b.User_Id as Online
			,a.Service_Id,aa.Station,aa.ModifiedDt,st.SiteName,a.CallActionId,b.User_Id,sl.Amount,sl.Skill_Id,sl.Level_Id,sl.Description,asbr.AgentSkillLevel
			from repdb.dbo.AgentLoginLogout b left join repdb.dbo.ACDCallDetail a on a.Service_Id = b.Service_Id and dateadd(hour,8,a.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt)
			left join repdb.dbo.AgentStateAudit aa on b.User_Id = aa.User_Id and DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,b.LoginDt)) / 1 * 1, 0)) = DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,aa.ModifiedDt)) / 1 * 1, 0)) 
			and aa.Agent_Index is not null left join RepUIDB.dbo.Stations s on aa.Station = s.Station left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid
			left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Service] service on service.Service_Id = a.Service_Id
			left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Agent_Skills ASK on ask.User_Id = b.User_Id
			left join [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Skill_Levels SL	on sl.Skill_Id = ask.Skill_Id and sl.Level_Id = ask.Level_Id
			left join repdb.dbo.ASBRCallSkillDetail asbr on asbr.Service_Id = a.Service_Id and dateadd(hour,8,asbr.callstartdt) between dateadd(hour,8,b.logindt)  and dateadd(hour,8,b.LogoutDt)
			where a.Service_Id IN (4000013,4000019,4000021,4000023,4000025, 4000027, 4000029) and a.CallActionId in (8, 18, 5, 6) and ask.Skill_Id not in (4000001, 4000002)
		)

		, filtered_AgentLogin_Count as (
			select 	DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval ,Count(distinct User_Id) as Count_Login ,Service_Id ,Station ,SiteName
			,CallActionId, Amount ,Skill_Id	,Level_Id ,Description ,user_id	,agentskillLevel from base_AgentLogin_Count
			group by CallStartDt ,Service_Id ,Station ,SiteName	,CallActionId ,Amount ,Skill_Id	,Level_Id ,Description	,user_id ,agentskillLevel
		)

		, grouped_Online as (
			select	FifteenMinuteInterval ,count(distinct Count_Login) as Count_Login ,(case when amount = 2 then 1 end) as Online_Novice ,(case when amount = 6 then 1 end) as Online_DevExpert
			,(case when amount = 10 then 1 end) as Online_Expert ,Service_Id ,SiteName ,Skill_Id ,user_id ,description ,agentskillLevel	from filtered_AgentLogin_Count
			group by FifteenMinuteInterval ,Service_Id ,SiteName ,Skill_Id ,amount ,user_id ,Description ,agentskillLevel
		)

		,grouped_Online_Final as (
			select 	FifteenMinuteInterval ,Count(distinct Count_Login) as Count_Login ,Count(distinct Online_Novice) as Online_Novice ,Count(distinct Online_DevExpert) as Online_DevExpert	,Count(distinct Online_Expert) as Online_Expert
			,user_id ,Service_Id ,SiteName ,Skill_Id ,description ,AgentSkillLevel from	grouped_Online where AgentSkillLevel IN (2,6,10)
			group by FifteenMinuteInterval ,Service_Id ,SiteName ,Skill_Id ,user_id	,description ,AgentSkillLevel
		)

		, group_Online_FInal_Abandoned as (
			select distinct	FifteenMinuteInterval ,Count(distinct Count_Login) as Count_Login ,Count(distinct Online_Novice) as Online_Novice ,Count(distinct Online_DevExpert) as Online_DevExpert
			,Count(distinct Online_Expert) as Online_Expert	,user_id ,Service_Id ,SiteName ,Skill_Id ,description ,AgentSkillLevel from	grouped_Online where agentskillLevel = 0 
			group by FifteenMinuteInterval ,Service_Id,SiteName,Skill_Id,user_id,description,AgentSkillLevel
		)
		
		,base_AgentLogin_Count_with_Skill_Level as (
			Select distinct	dateadd(hour, 8 ,acd.CallStartDt) as CallStartDt ,acd.Service_Id ,asbr.Skill_Id	,asbr.AgentSkillLevel ,agt.User_Id
			,(select top 1 level_id from [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].Agent_SkillsAudit aa where aa.user_id = agt.User_Id and aa.Skill_Id = asbr.Skill_Id
			and aa.ModifiedDt <= acd.CallStartDt and aa.ModifiedDt <= agt.LoginDt order by ModifiedDt desc)	as level_id	,aa.Station	,st.SiteName from REPDB.dbo.ACDCallDetail acd left join REPDB..AgentLoginLogout agt
			on (acd.CallStartDt between agt.LoginDt and agt.LogoutDt or acd.CallStartDt >= agt.LoginDt and agt.LogoutDt is null)
			left join REPDB..ASBRCallSkillDetail asbr on acd.SeqNum = asbr.SeqNum and acd.Service_Id = asbr.Service_Id left join repdb.dbo.AgentStateAudit aa
			on agt.User_Id = aa.User_Id and DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,agt.LoginDt)) / 1 * 1, 0)) 
			= DATEADD(HOUR, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,aa.ModifiedDt)) / 1 * 1, 0)) 
			and aa.Agent_Index is not null left join RepUIDB.dbo.Stations s on aa.Station = s.Station left join RepUIDB.dbo.Sites st on s.SiteGuid = st.SiteGuid where
			acd.Service_Id IN (4000013) and asbr.Skill_Id not in (4000001, 4000002)
		),
		
		base_table_skill_levels as (select * from [VW12PCTIDB01\UIP_CONFIG].[config_epro].[dbo].[Skill_Levels] SKL)

		, agent_login_with_Skill_Description_Answered as (
			select DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval
			,Service_Id	,User_Id ,a.Skill_Id ,AgentSkillLevel ,a.level_id ,b.description ,a.SiteName ,a.CallStartDt	from base_AgentLogin_Count_with_Skill_Level a
			left join base_table_skill_levels b	on a.Skill_Id = b.Skill_Id	and a.level_id = b.Level_Id	where AgentSkillLevel IN (2,6,10)
		), 
		agent_login_with_Skill_Description_Abandoned as (
			select DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0,CallStartDt)) / 15 * 15, 0)) as FifteenMinuteInterval
			,Service_Id,User_Id,a.Skill_Id,AgentSkillLevel,a.level_id,b.description,a.SiteName,a.CallStartDt from base_AgentLogin_Count_with_Skill_Level a 	LEFT join base_table_skill_levels b
			on a.Skill_Id = b.Skill_Id	and a.level_id = b.Level_Id	where AgentSkillLevel NOT IN (2,6,10)
		)

		, final_query as (select a.FifteenMinuteInterval,a.SiteName as Site,i.SiteName as SiteNameDNIS	,COALESCE(a.SiteName, i.siteName) as SiteName ,a.Offered ,b.Answered ,c.Answered as NoviceAnswered ,d.Answered as DevExpertAnswered
		,e.Answered as ExpertAnswered ,b.WithinThreshold ,b.ASA_Group ,b.AHT_Group	,c.ASA_Novice ,c.AHT_Novice	,d.ASA_DevExpert ,d.AHT_DevExpert ,e.ASA_Expert	,e.AHT_Expert ,a.AgentSkillLevel ,a.Service_Id	,a.Service_c ,a.Skill_Id
		,a.Skill_Desc ,a.User_Id ,a.CallStartDt
		,case when a.AgentSkillLevel = 0 then 1 else 0 end as Expert_Offered
		from calls_offered_table a
		left join answered_Group_With_Avg b on a.FifteenMinuteInterval = b.FifteenMinuteInterval and a.Service_Id = b.Service_Id and a.Skill_Id = b.Skill_Id and a.AgentSkillLevel = b.AgentSkillLevel	and a.User_Id = b.User_Id
		and a.SiteName = b.SiteName	and b.CallStartDt = a.CallStartDt
		left join novice_group_with_AVG C	on a.FifteenMinuteInterval = c.FifteenMinuteInterval and a.Service_Id = c.Service_Id and a.Skill_Id = c.Skill_Id and a.AgentSkillLevel = c.AgentSkillLevel and a.User_Id = c.User_Id
		and a.SiteName = c.SiteName	and c.CallStartDt = a.CallStartDt 
		left join --expert_offered
		devExpert_group_with_AVG D	on a.FifteenMinuteInterval = d.FifteenMinuteInterval and a.Service_Id = d.Service_Id and a.Skill_Id = d.Skill_Id and a.AgentSkillLevel = d.AgentSkillLevel	and a.User_Id = d.User_Id
		and a.SiteName = d.SiteName	and d.CallStartDt = a.CallStartDt
		left join Expert_group_with_AVG E on a.FifteenMinuteInterval = e.FifteenMinuteInterval	and a.Service_Id = e.Service_Id	and a.Skill_Id = e.Skill_Id	and a.AgentSkillLevel = e.AgentSkillLevel	and a.User_Id = e.User_Id
		and a.SiteName = e.SiteName	and e.CallStartDt = a.CallStartDt
		left join final_query_abandoned i on i.FifteenMinuteInterval = a.FifteenMinuteInterval	and i.Service_Id = a.Service_Id	and i.AgentSkillLevel = a.AgentSkillLevel	and i.CallStartDt = a.CallStartDt), 
		finished_query as (	select DATEADD(MINUTE, DATEDIFF(MINUTE, 8, DATEADD(MINUTE, 15, DATEADD(hour, 0, a.fifteenMinuteInterval))) / 15 * 15, 0) as 'Interval',
		DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, a.fifteenMinuteInterval)) / 15 * 15, 0)) as '15MinsInterval',
		DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, a.fifteenMinuteInterval)) / 30 * 30, 0)) as '30MinsInterval',
		DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, a.fifteenMinuteInterval)) / 60 * 60, 0)) as '60MinsInterval',
		CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,a.fifteenMinuteInterval))) %7 , dateadd(hour, 0,a.fifteenMinuteInterval))), 101) as Sunday,
		CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,a.fifteenMinuteInterval))) %7 , dateadd(hour, 0,a.fifteenMinuteInterval))), 101) as Saturday,
		convert(varchar(10), dateadd(hour, 0,a.fifteenMinuteInterval), 101) + ' ' +
		format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, a.fifteenMinuteInterval)) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Minus15Mins,
		convert(varchar(10), dateadd(hour, 0,a.fifteenMinuteInterval), 101) + ' ' +
		format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, a.fifteenMinuteInterval)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Minus30Mins,
		convert(varchar(10), dateadd(hour, 0,a.fifteenMinuteInterval), 101) + ' ' +
		format(CAST(DATEADD(hour, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, a.fifteenMinuteInterval)) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Minus60Mins,
		datepart(wk, dateadd(hour, 0, a.fifteenMinuteInterval)) as Week_count,datepart(w, dateadd(hour, 0, a.fifteenMinuteInterval)) as Day_Count, datename(dw, dateadd(hour, 0, a.fifteenMinuteInterval)) as Day_Name,
		convert(varchar(10), dateadd(hour, 0,a.fifteenMinuteInterval), 101) as perDate, DATEPART(M, DATEADD(hour, 0, a.fifteenMinuteInterval)) AS Month_Count,
		cast(datename(m, dateadd(hour,8,a.fifteenMinuteInterval)) as varchar(10)) + ', ' + cast(year(dateadd(hour,8,a.fifteenMinuteInterval)) as varchar(10)) AS MONTH_NAME,
		cast(year(dateadd(hour,8,a.fifteenMinuteInterval)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,8,a.fifteenMinuteInterval)) as varchar(10)) as Year_Month,
		YEAR(DATEADD(hour, 0, a.fifteenMinuteInterval)) AS YEAR,DATEADD(hour, 0, a.fifteenMinuteInterval) AS DATE_TIME
		,case when datepart(hour, dateadd(hour, 0, a.FifteenMinuteInterval)) <= 5 then convert(varchar(10), dateadd(DAY, -1,dateadd(hour, 0,a.FifteenMinuteInterval)), 101) 
		else convert(varchar(10), dateadd(hour, 0,a.FifteenMinuteInterval), 101) end + ' ' +
			case when datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) <= 5 or datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) >= 22 then '10PM - 6AM'
			when datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) > = 14 and datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) <= 21 then '2PM - 10PM'
			when datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) >= 6 and datepart(hour, dateadd(hour, 0, a.fifteenMinuteInterval)) <= 13 then '6AM - 2PM' end
			as Service_Shift, a.FifteenMinuteInterval
		,case when a.SiteName is null then 'MOC' else a.SiteName end as SiteName
		,a.Offered,a.Answered,a.NoviceAnswered,a.DevExpertAnswered,a.ExpertAnswered,a.WithinThreshold,a.ASA_Group,a.AHT_Group,a.ASA_Novice,a.AHT_Novice,a.ASA_DevExpert,a.AHT_DevExpert,a.ASA_Expert,a.AHT_Expert,fla.User_Id as AnsUser_Id
		,aban.User_Id as AbanUser_id,COALESCE(fla.User_Id, aban.User_Id) as User_ID,aban.Description as Description_aban,fla.Description as Description_Answered,COALESCE(fla.description, aban.Description) as Description
		,a.AgentSkillLevel,a.Service_Id,a.Service_c,a.Skill_Id,a.Skill_Desc,Expert_Offered + case when a.ExpertAnswered is null then 0 else a.ExpertAnswered end as Offered_Expert from final_query a
		left join agent_login_with_Skill_Description_Answered FLA on a.FifteenMinuteInterval = FLA.FifteenMinuteInterval and a.Skill_Id = fla.Skill_Id and a.Service_Id = fla.Service_Id and a.SiteName = fla.SiteName and a.User_Id = fla.User_Id and a.CallStartDt = fla.CallStartDt
		left join agent_login_with_Skill_Description_Abandoned Aban on a.FifteenMinuteInterval = Aban.FifteenMinuteInterval and a.Skill_Id = Aban.Skill_Id and a.Service_Id = Aban.Service_Id and a.SiteName = Aban.SiteName and a.AgentSkillLevel = aban.AgentSkillLevel and a.callstartdt = aban.callstartdt)
		select * from finished_query where Service_Id IN (".$svd.") and Skill_Id IN (".$skd.") and SiteName IN (".$sid.") and FifteenMinuteInterval >= '".$datefrom."' and FifteenMinuteInterval <= '".$dateto."'";

		$query = $this->db->query($cmd);
		$output = $query->result();
		return $query;
	}
	
	public function get_vhis6a_data($datefrom,$dateto,$siteid,$trunkids,$range){
		$siteid = rtrim($siteid,",");
		$temp = explode(",",$siteid);
		$siteid = "'" . implode ( "', '", $temp ) . "'";
		$trunkids = rtrim($trunkids,",");
		$temp = explode(",",$trunkids);
		$trunkids = "'" . implode ( "', '", $temp ) . "'";	
		$cmd = "--vhis6a
			with BaseResult as(	select  distinct origDeviceName,DATEADD(HOUR,0,dateTimeOrigination) as dateTimeOrigination,sum(duration) UsageTime,count(*) as Total,
				sum(case when CallType = 'Abandoned' then 1 else 0 end) as Abandoned, sum(case when CallType = 'Answered' then 1 else 0 end) as Answered, sum(case when CallType = 'Abandoned' then duration else 0 end) as AbandonedDelay, 
				sum(case when CallType = 'Answered' then answerDelay else 0 end) as AnsweredDelay, sum(case when answerDelay < 0 then 0 else answerDelay end) as aswerDelay from
				(select DATEADD(MINUTE,DATEDIFF(minute,0,dateTimeOrigination)/15*15,0) as dateTimeOrigination,
					CiscoCDR.origDateTimeConnect,CiscoCDR.dateTimeConnect ,CiscoCDR.dateTimeDisconnect,CiscoCDR.destDeviceName,
					CiscoCDR.origDeviceName,CiscoCDR.origDuration,
					 case 
						when CiscoCDR.dateTimeConnect = '1970-01-01 00:00:00.000' then 'Abandoned'
					 else
						'Answered'
					 end as CallType, DATEDIFF(SECOND,CiscoCDR.dateTimeOrigination,CiscoCDR.dateTimeDisconnect) as duration,DATEDIFF(SECOND,CiscoCDR.dateTimeOrigination,CiscoCDR.dateTimeConnect) as answerDelay
					from (select origDeviceName,destDeviceName,duration as origDuration,DATEADD(SECOND,CONVERT(BIGINT,dateTimeConnect),'19700101') as dateTimeConnect,
					DATEADD(SECOND,CONVERT(BIGINT,dateTimeOrigination),'19700101') as origDateTimeConnect,DATEADD(SECOND,CONVERT(BIGINT,dateTimeOrigination),'19700101') as dateTimeOrigination,
					DATEADD(SECOND,CONVERT(BIGINT,dateTimeDisconnect),'19700101') as dateTimeDisconnect	from [REPDB].[dbo].[CiscoCDR]
					WHERE DATEADD(HOUR, 8, DATEADD(SECOND,CONVERT(BIGINT,dateTimeOrigination),'19700101')) >= '". $datefrom . "' --'02/06/2019' 
					AND DATEADD(HOUR, 8, DATEADD(SECOND,CONVERT(BIGINT,dateTimeOrigination),'19700101')) <= '". $dateto . "' --'02/07/2019'
					AND origDeviceName IN (". $trunkids . ")) CiscoCDR ) Derived_Table
				group by origDeviceName,dateTimeOrigination)

			select origDeviceName, DATEADD(hour, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, dateTimeOrigination)) / 15 * 15, 0)) as '15MinsInterval',
			DATEADD(hour, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, dateTimeOrigination)) / 30 * 30, 0)) as '30MinsInterva',
			DATEADD(hour, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, dateTimeOrigination)) / 60 * 60, 0)) as '60MinsInterval',
			CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8,dateTimeOrigination))) %7 , dateadd(hour, 8,dateTimeOrigination))), 101) as Sunday,
			CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8,dateTimeOrigination))) %7 , dateadd(hour, 8,dateTimeOrigination))), 101) as Saturday,
			convert(varchar(10), dateadd(hour, 8,dateTimeOrigination), 101) + ' ' +
			format(CAST(DATEADD(hour, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, dateTimeOrigination)) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Minus15Mins,
			convert(varchar(10), dateadd(hour, 8,dateTimeOrigination), 101) + ' ' +
			format(CAST(DATEADD(hour, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, dateTimeOrigination)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Minus30Mins,
			convert(varchar(10), dateadd(hour, 8,dateTimeOrigination), 101) + ' ' +
			format(CAST(DATEADD(hour, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, dateTimeOrigination)) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Minus60Mins,
			datepart(wk, dateadd(hour, 8, dateTimeOrigination)) as Week_count,datepart(w, dateadd(hour, 8, dateTimeOrigination)) as Day_Count, 
			datename(dw, dateadd(hour, 8, dateTimeOrigination)) as Day_Name,DATEPART(M, DATEADD(hour, 8, dateTimeOrigination)) AS Month_Count,
			cast(datename(m, dateadd(hour,8,dateTimeOrigination)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,dateTimeOrigination)) as varchar(10)) AS MONTH_NAME,
			cast(year(dateadd(hour,8,dateTimeOrigination)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,dateTimeOrigination)) as varchar(10)) as Year_Month,
			YEAR(DATEADD(hour, 8, dateTimeOrigination)) AS YEAR,DATEADD(hour, 8, dateTimeOrigination) AS DATE_TIME,	count(dateTimeOrigination) as Count,
			convert(varchar(10), dateadd(hour, 8,dateTimeOrigination), 101) +' '+
			case when datepart(hour, dateadd(hour, 8, dateTimeOrigination)) <= 5 or datepart(hour, dateadd(hour, 8, dateTimeOrigination)) >= 22 then '10PM - 6AM'
			when datepart(hour, dateadd(hour, 8, dateTimeOrigination)) > = 14 and datepart(hour, dateadd(hour, 8, dateTimeOrigination)) <= 21 then '2PM - 10PM'
			when datepart(hour, dateadd(hour, 8, dateTimeOrigination)) >= 6 and datepart(hour, dateadd(hour, 8, dateTimeOrigination)) <= 13 then '6AM - 2PM' end
			as Service_Shift,
			dateTimeOrigination,Abandoned,Answered, convert(time(0), dateadd(second, AbandonedDelay,0))  AbandonedDelay, convert(time(0), dateadd(second, AnsweredDelay,0)) AnsweredDelay , convert(time(0), dateadd(second, UsageTime,0)) UsageTime,Total, 
			(Cast(UsageTime as float)/Cast(Total as float)) as Utilization, CONVERT(VARCHAR(10),DATEADD(HOUR,8,dateTimeOrigination) , 101) as perDate from BaseResult
			WHERE DATEADD(HOUR,8,dateTimeOrigination) >= '". $datefrom . "' AND DATEADD(HOUR,8,dateTimeOrigination) <= '". $dateto . "' and
			origDeviceName IN (". $trunkids . ") ";
			if($range == "Morning") {
				$cmd .= " AND datepart(hour, dateadd(hour, 8, dateTimeOrigination)) >= 6 and datepart(hour, dateadd(hour, 8, dateTimeOrigination)) <= 13 ";
			} elseif($range == "Afternoon") {
				$cmd .= " AND datepart(hour, dateadd(hour, 8, dateTimeOrigination)) >= 14 and datepart(hour, dateadd(hour, 8, dateTimeOrigination)) <= 21 ";
			} elseif($range == "Graveyard") {
				$cmd .= " AND datepart(hour, dateadd(hour, 8, dateTimeOrigination)) <= 5 or datepart(hour, dateadd(hour, 8, dateTimeOrigination)) >= 22 ";	
			}
			$cmd .= " group by 
			DATEADD(hour, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 15, dateTimeOrigination)) / 15 * 15, 0)) ,
			DATEADD(hour, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 30, dateTimeOrigination)) / 30 * 30, 0)),
			DATEADD(hour, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60, dateTimeOrigination)) / 60 * 60, 0)),
			CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8,dateTimeOrigination))) %7 , dateadd(hour, 8,dateTimeOrigination))), 101),
			CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 8,dateTimeOrigination))) %7 , dateadd(hour, 8,dateTimeOrigination))), 101),
			convert(varchar(10), dateadd(hour, 8,dateTimeOrigination), 101) + ' ' +
			format(CAST(DATEADD(hour, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, dateTimeOrigination)) / 15 * 15, 0)) as Datetime), 'hh:mm tt'),
			convert(varchar(10), dateadd(hour, 8,dateTimeOrigination), 101) + ' ' +
			format(CAST(DATEADD(hour, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, dateTimeOrigination)) / 30 * 30, 0)) as Datetime), 'hh:mm tt'),
			convert(varchar(10), dateadd(hour, 8,dateTimeOrigination), 101) + ' ' +
			format(CAST(DATEADD(hour, 8, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, dateTimeOrigination)) / 60 * 60, 0)) as Datetime), 'hh:mm tt'),
			datepart(wk, dateadd(hour, 8, dateTimeOrigination)),datepart(w, dateadd(hour, 8, dateTimeOrigination)), 
			datename(dw, dateadd(hour, 8, dateTimeOrigination)),DATEPART(M, DATEADD(hour, 8, dateTimeOrigination)),
			cast(datename(m, dateadd(hour,0,dateTimeOrigination)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,dateTimeOrigination)) as varchar(10)),
			cast(year(dateadd(hour,0,dateTimeOrigination)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,dateTimeOrigination)) as varchar(10)),
			YEAR(DATEADD(hour, 8, dateTimeOrigination)),DATEADD(hour, 8, dateTimeOrigination),
			dateTimeOrigination,Abandoned,Answered,AbandonedDelay, convert(time(0), dateadd(second, AnsweredDelay,0)) , convert(time(0), dateadd(second, UsageTime,0)) ,Total,
			(Cast(UsageTime as float)/Cast(Total as float)),CONVERT(VARCHAR(10),dateTimeOrigination , 101), origDeviceName,
			convert(varchar(10), dateadd(hour, 8,dateTimeOrigination), 101) +' '+
			case when datepart(hour, dateadd(hour, 8, dateTimeOrigination)) <= 5 or datepart(hour, dateadd(hour, 8, dateTimeOrigination)) >= 22 then '10PM - 6AM'
			when datepart(hour, dateadd(hour, 8, dateTimeOrigination)) > = 14 and datepart(hour, dateadd(hour, 8, dateTimeOrigination)) <= 21 then '2PM - 10PM'
			when datepart(hour, dateadd(hour, 8, dateTimeOrigination)) >= 6 and datepart(hour, dateadd(hour, 8, dateTimeOrigination)) <= 13 then '6AM - 2PM' end
			order by origDeviceName desc,dateTimeOrigination";
			$query = $this->db->query($cmd);
			$output = $query->result();
			return $query;
	}
	
	public function vhis_16($dateFrom, $dateTo, $service){
	 $from = date('m/d/Y H:i:s', strtotime($dateFrom)); $to = date('m/d/Y H:i:s', strtotime($dateTo));

	 $cmd = "SELECT DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 15, CallDate)) / 15 * 15, 0) as Interval,Convert(varchar, CallDate, 101) as CallDate,DATEADD(HOUR, 0,CallDate) AS CALLSTARTDT,
			DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 15, CallDate)) / 15 * 15, 0)) as \"15MinsInterval\",
			DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 30, CallDate)) / 30 * 30, 0)) as \"30MinsInterval\",
			DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 60, CallDate)) / 60 * 60, 0)) as \"60MinsInterval\",
			CONVERT(VARCHAR(20), (dateadd(dd, 0 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,CallDate))) %7 , dateadd(hour, 0,CallDate))), 101) as Sunday,
			CONVERT(VARCHAR(20), (dateadd(dd, 6 - (@@datefirst +6 + datepart(dw, dateadd(hour, 0,CallDate))) %7 , dateadd(hour, 0,CallDate))), 101) as Saturday,
			convert(varchar(10), dateadd(hour, 0,CallDate), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, CallDate)) / 15 * 15, 0)) as Datetime), 'hh:mm tt') as Minus15Mins,
			convert(varchar(10), dateadd(hour, 0,CallDate), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, CallDate)) / 30 * 30, 0)) as Datetime), 'hh:mm tt') as Minus30Mins,
			convert(varchar(10), dateadd(hour, 0,CallDate), 101) + ' ' +
			format(CAST(DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, CallDate)) / 60 * 60, 0)) as Datetime), 'hh:mm tt') as Minus60Mins,
			DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, CallDate)) / 15 * 15, 0)) as Minus15Mins1,
			DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, CallDate)) / 30 * 30, 0)) as Minus30Mins1,
			DATEADD(HOUR, 0, DATEADD(MINUTE, DATEDIFF(MINUTE, 0, DATEADD(MINUTE, 0, CallDate)) / 60 * 60, 0)) as Minus60Mins1,
			datepart(wk, dateadd(hour, 0, CallDate)) as Week_count, datepart(w, dateadd(hour, 0, CallDate)) as Day_Count, 
			datename(dw, dateadd(hour, 0, CallDate)) as Day_Name,convert(varchar(10), dateadd(hour, 0,CallDate), 101) as perDate,
			DATEPART(M, DATEADD(HOUR, 0, CallDate)) AS Month_Count,
			cast(datename(m, dateadd(hour,0,CallDate)) as varchar(10)) + ', ' + cast(year(dateadd(hour,0,CallDate)) as varchar(10)) AS MONTH_NAME,
			cast(year(dateadd(hour,0,CallDate)) as varchar(10)) + ', ' + cast(datename(m, dateadd(hour,0,CallDate)) as varchar(10)) as Year_Month,
			YEAR(DATEADD(HOUR, 0, CallDate)) AS YEAR,DATEADD(HOUR, 0, CallDate) AS DATE_TIME,
			convert(varchar(10), dateadd(hour, 0,CallDate), 101) +' '+
			case when datepart(hour, dateadd(hour, 0, CallDate)) <= 5 or datepart(hour, dateadd(hour, 0, CallDate)) >= 22 then '10PM - 6AM'
			when datepart(hour, dateadd(hour, 0, CallDate)) > = 14 and datepart(hour, dateadd(hour, 0, CallDate)) <= 21 then '2PM - 10PM'
			when datepart(hour, dateadd(hour, 0, CallDate)) >= 6 and datepart(hour, dateadd(hour, 0, CallDate)) <= 13 then '6AM - 2PM' end
			as Service_Shift,b.Hotlines,[Medium],[DPAAgreement],[Language],[MainMenu],[OutagesAndIncidents],[BillingAndPayments],[ProductsServicesAndPrograms],[Others],[OtherMenus],[SessionID],[UMID],[LoadAndBalance],[NewApplicationService]
			FROM [VW12PCTICXPDB01].[TransactionDB].[dbo].[IVRSMenu] a INNER JOIN [VW12PCTICXPDB01].[TransactionDB].[dbo].[TblHotlineNumbers] b on a.DNIS = b.AssociatedDNIS
			where Convert(varchar, CallDate, 101) >= '".$from."' AND Convert(varchar, CallDate, 101) <= '".$to."' ";
			if($service <> ""){
				$cmd .=  "AND b.Hotlines IN (".$service.")";
			}
			$cmd.=" order by Convert(varchar, CallDate, 101) ASC";
				$query = $this->db->query($cmd);
				return $query;
	}
	
}