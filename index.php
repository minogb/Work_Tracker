<!DOCTYPE html>

<html>
    <head>
		<link rel="stylesheet" href="./styles.css">
        <meta charset="UTF-8">
        <title>Work Tracker</title>
    </head>
	<style>
		table {
		  font-family: arial, sans-serif;
		  border-collapse: collapse;
		  width: 100%;
		}

		td, th {
		  border: 1px solid #dddddd;
		  text-align: left;
		  padding: 8px;
		}

		tr:nth-child(even) {
		  background-color: #dddddd;
		}
	</style>
    <body>
		<div class="topRow">
			<input type="password" id="userId" name="userId" tabindex=-1 placeholder="User Id">
			<input type="date" id="day" name="userId" tabindex=-1 placeholder="End">
			<button  tabindex=1 id="addJob">Add Job</button>
		</div>
		<div class="timeEntryContainer">
		</div>
		
		<div style="float:left;width:100%;">
			 <div class="floatLeft">Total Time: </div>
			 <div class="totalTime floatLeft"></div>
		</div>
		<div style="float:left;width:100%;">
			<h2>Time by Billing</h2>
			<div class="billing" style="float:left;width:100%;">
			</div>
		</div>
		<div style="float:left;width:100%;">
				<h2>Time by File</h2>
			 <div class="files floatLeft"></div>
		</div>
		<script src="./jquery.js">
		</script>
		<script src="./jqueryCookie.js">
		</script>
		<script>
		var userId = -1;
		var uniqueId = 0;
		Date.prototype.toDateInputValue = (function() {
			var local = new Date(this);
			local.setMinutes(this.getMinutes() - this.getTimezoneOffset());
			return local.toJSON().slice(0,10);
		});
		Date.prototype.toTimeInputValue = (function() {
			var local = new Date(this);
			local.setMinutes(this.getMinutes() - this.getTimezoneOffset());
			return local.toJSON().slice(11,19);
		});
		//INT entryId
		//STRING billing
		//STRING file
		//STRING project
		//TIME MM:HH start
		//TIME MM:HH start
		function createNewTimeEntry(entryId, billing,file,project,start,end,location,customerName){
			var $container = $("<div/>",{
				"class":"timeEntry ",
				id:uniqueId++,
				name:entryId
			});
			var $exit = $("<button/>",{
					"class":"removeThisEntry",
					html: 'X',
					tabindex:-1
			});
			var $customerName = $("<input/>",{
					type: 'text',
					name: 'customerName',
					"class": "customerName  data",
					placeholder: 'Customer Name',
					value:customerName
			});
			var $billing = $("<input/>",{
					type: 'text',
					name: 'billing',
					"class": "billing  data",
					placeholder: 'Billing #: 555555',
					value:billing
			});
			var $file = $("<input/>",{
					type: 'text',
					name: 'file',
					"class": "file  data",
					placeholder: 'File #: 555555',
					value:file
			});
			var $project = $("<input/>",{
					type: 'text',
					name: 'project',
					"class": "project  data",
					placeholder: 'Project #: XX',
					value:project
			});
			var $location = $("<input/>",{
					type: 'text',
					name: 'location',
					"class": "location  data",
					placeholder: 'location #: XX',
					value:location
			});
			var $startTime = $("<input/>",{
					type: 'time',
					"class" : "start without_ampm data",
					name: 'startTime',
					value:start
			});
			var $endTime = $("<input/>",{
					type: 'time',
					"class" : "end without_ampm data",
					name: 'endTime',
					value:end
			});
			$container.append($exit);
			$container.append($customerName);
			$container.append("<br/>");
			$container.append($billing);
			$container.append("<br/>");
			$container.append($file);
			$container.append("<br/>");
			$container.append($project);
			$container.append("<br/>");
			$container.append($location);
			$container.append("<br/>");
			
			$timeSetContainer = $("<div/>",{"class":"timeSetContainer"});
			$timeContiner = $("<div/>",{"class":"floatLeft"});
			$timeContiner.append("Start");
			$timeContiner.append('<br/> ');
			$timeContiner.append("End");
			$timeSetContainer.append($timeContiner);
			
			$timeContiner = $("<div/>",{"class":"floatRight"});
			$timeContiner.append($startTime);
			$timeContiner.append('<br/> ');
			$timeContiner.append($endTime);
			$timeSetContainer.append($timeContiner);
			
			$container.append($timeSetContainer);
			$exit.click(function(){
				removeEntry($(this).parent().attr('name'),$(this).parent().attr('id'));
			});
			$billing.focusout(function(){SaveJobEntry($(this).parent());});
			$file.focusout(function(){SaveJobEntry($(this).parent());});
			$project.focusout(function(){SaveJobEntry($(this).parent());});
			$customerName.focusout(function(){SaveJobEntry($(this).parent());});
			$location.focusout(function(){SaveJobEntry($(this).parent());});
			$startTime.focusout(function(){SaveJobEntry($(this).parent().parent().parent());});
			$endTime.focusout(function(){SaveJobEntry($(this).parent().parent().parent());});
			return $container;
		}
		function removeEntry(entryId,uniqueId){
			if(!entryId){
				$("#" + uniqueId).remove();
				return;
			}
			var $json = {
				method : "removeEntry",
				id: entryId,
				uniqueId: uniqueId
			}
			$.post("./request.php",{request:$json
				}).done(function(data){removeEntryHelper(data)});
		}
		function removeEntryHelper(data){
			var $abc = $.parseJSON(data);
			if($abc.success){
				$("#" + $abc.uniqueId).remove();
			}
			UpdateSummary();
		}
		//JSON
		function loadAndCreateHelper(data){
			//$(".timeEntryContainer").append(data);
			var $abc = $.parseJSON(data);
			//alert($abc.entries);
			if($abc['success'] == true){
				var $i = 0;
				while($i < $abc.entries.length){
					$index = $abc.entries[$i++];
					$(".timeEntryContainer").append(createNewTimeEntry($index.id,$index.billing,$index.file,$index.project,$index.start,$index.end,$index.location,$index.customerName));
				}
				UpdateSummary();
			}
			else{
				alert($abc.reason);
			}
			
			//Always have one entry
			$(".timeEntryContainer").append(createNewTimeEntry());
		}
		//HTML div entry
		function SaveJobEntry(entry){
			if(ShouldSaveEntry(entry)){
			var $json = {
				method : "saveEntry",
				id: $(entry).attr('name'),
				userId: userId,
				uniqueId: $(entry).attr('id'),
				customerName: $(entry).find(".customerName").val(),
				billing:$(entry).find(".billing").val(),
				file:$(entry).find(".file").val(),
				project:$(entry).find(".project").val(),
				location:$(entry).find(".location").val(),
				start:$(entry).find(".start").val(),
				end: $(entry).find(".end").val(),
				day: $("#day").val()
			}
			//send request -> request
			$.post("./request.php",{request:$json
				}).done(function(data){SaveJobEntryHelper(data)});
			}
			else{
				//	$(document).append("not saving");
			}
		}
		function SaveJobEntryHelper(data){
			var $abc = $.parseJSON(data);
			$("#"+$abc.uniqueId).attr('name',$abc.id);
			UpdateSummary();
		}
		//HTML div entry
		function ShouldSaveEntry(entry){
			if(userId < 0)
				return false;
			if(!entry)
				return false;
			$billing = $(entry).find(".billing");
			if(!$billing || $billing.length == 0 || $billing.val() == "")
				return false;
			$file = $(entry).find(".file");
			if(!$file || $file.length == 0 || $file.val() == "")
				return false;
			$project = $(entry).find(".project");
			if(!$project || $project.length == 0 || $project.val() == "")
				return false;
			$start = $(entry).find(".start");
			if(!$start || $start.length == 0 || $start.val() == "")
				return false;
			return true;
		}
		function LoadAllEntries(){
			var $container = $(".timeEntryContainer");
			$container.empty();
			//Get json entry for each
			$.post("./request.php",{request:{
					method:"getAllEntries",
					userId:userId,
					day:$("#day").val()
				}}).done(function(data){loadAndCreateHelper(data)});
		}
		//JSON entry
		function AddHtmlForEntry(entry){
		}
		//int id
		function SetUserId(id){
			userId = id;
			if(userId > 0){
				$("#userId").val(userId);
				Cookies.set("userId",userId);
				$("#userId").attr('tabindex', -1);
				$("#userId").removeClass("badInput");
				LoadAllEntries();
			}
			else{
				$("#userId").focus();
				$("#userId").attr('tabindex', 0);
				$("#userId").addClass("badInput");
			}
		}
		function UpdateSummary(){
			var $TotalForFile = new Array();
			var $TotalForBilling = new Array();
			var $ProjectsForFile = new Array();
			var $TotalTime = 0;
			var $entries = $(".timeEntry");
			var $numberOfEntries = $entries.length;
			var $index = 0;
			while($index < $numberOfEntries){
				$entry = $entries[$index++];
				if(!ShouldSaveEntry($entry))
					continue;
				var $json = {
					id: $($entry).attr('name'),
					uniqueId: $($entry).attr('id'),
					customerName: $($entry).find(".customerName").val(),
					billing:$($entry).find(".billing").val(),
					file:$($entry).find(".file").val(),
					project:$($entry).find(".project").val(),
					location:$($entry).find(".location").val(),
					start:$($entry).find(".start").val(),
					end: $($entry).find(".end").val()
				}
				$startHours = parseInt($json.start.substring(0,2));
				$startMinutes = parseInt($json.start.substring(3,5));
				$endHours = parseInt($json.end.substring(0,2));
				$endMinutes = parseInt($json.end.substring(3,5));
				if($endHours< $startHours){
					continue;
				}
				$minutes = (($endMinutes/60) - ($startMinutes/60));
				$time = ($endHours - $startHours) + $minutes;
				$TotalTime = $TotalTime ? $TotalTime + $time : $time;
				var $file={
					
				}
				if(!$TotalForFile[$json.file]){
					$TotalForFile[$json.file] = {
						time: 0,
						customer:$json.customerName,
						billing:$json.billing,
						location:$json.location,
						project:[]
					};
				}
				$TotalForFile[$json.file].time += $time;
				$TotalForFile[$json.file].project.push($json.project);
				$TotalForFile[$json.file].project.push(',');
				$TotalForBilling[$json.billing] = $TotalForBilling[$json.billing] ? $TotalForBilling[$json.billing] + $time : $time;
				if(!$ProjectsForFile[$json.file]){
					$ProjectsForFile[$json.file] = new Array();
				}
				$ProjectsForFile[$json.file][$ProjectsForFile[$json.file].length] = $json.project;
				
			}
			//alert($TotalForBilling);
			$(".billing").empty();
			for(var key in $TotalForBilling){
				var $container = $("<div/>");
				$container.html(key + ": " + $TotalForBilling[key]);
				$(".billing").append($container);
			}
			$(".files").empty();
			var $table = $("<table/>");
			var $row = $("<tr/>");
			$row.append($("<th/>").html("Billing"));
			$row.append($("<th/>").html("File"));
			$row.append($("<th/>").html("Customer Name"));
			$row.append($("<th/>").html("Location"));
			$row.append($("<th/>").html("Projects"));
			$row.append($("<th/>").html("Time"));
			$table.append($row);
			for(var key in $TotalForFile){
				$TotalForFile[key].project.pop();
				$row = $("<tr/>");
				$row.append($("<td/>").html($TotalForFile[key].billing));
				$row.append($("<td/>").html(key));
				$row.append($("<td/>").html($TotalForFile[key].customer));
				$row.append($("<td/>").html($TotalForFile[key].location));
				$row.append($("<td/>").html($TotalForFile[key].project));
				$row.append($("<td/>").html($TotalForFile[key].time));
				$table.append($row);
			}
			
			$(".files").append($table);
			$(".totalTime").html($TotalTime);
		}
		$(document).ready(function() {
			$("#day").val(new Date().toDateInputValue());
			SetUserId(Cookies.get("userId"));
			$("#addJob").click(function(){
				$(".timeEntryContainer").append(createNewTimeEntry());
			});
			
			$("#userId").focusout(function(){
				SetUserId($("#userId").val());
			});
			$("#day").focusout(function(){
				LoadAllEntries();
			});
			$("#day").change(function(){
				LoadAllEntries();
			});
		});
</script>
    </body>
</html>
