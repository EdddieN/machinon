<?php

	require_once("CTQueryManager.php");

	$sensorOptions = [
		"30,39" => "Current",
		"13,17" => "Power",
	];
	$nodeId = 3;
	if (!empty($_POST)) {
		// collect data from post and group it by adc channel
		$channelsData = [];
		$errors = [];
		foreach ($_POST as $key => $value) {
			$data = explode("-", $key);
			if (count($data) !== 2 || !is_numeric($data[1]) || empty($value) && $value !== "0") {
				continue;
			}
			$configName = $data[0];
			$ctChannel = $data[1];
			// validation logic
			switch ($configName) {
                case "multiplier":
                    if (!is_numeric($value)) {
                        $errors[$ctChannel][$configName] = "Multiplier should be a numeric";
                        continue 2;
                    }
                    $value = (float) $value;
					break;
			}
			if (!isset($channelsData[$ctChannel])) {
				$channelsData[$ctChannel] = [];
			}
			$channelsData[$ctChannel][$data[0]] = $value;
		}
		
		if (empty($errors)) {
			$queries = [];
			foreach ($channelsData as $ctId => $data) {
				$queries = array_merge($queries, CTQueryManager::generateSetQueriesForChannel($nodeId, $ctId, $data));
			}
			if (!empty($queries)) {
				$queries = implode("\n", $queries);
				file_put_contents("node{$nodeId}.conf", $queries . "\n");
				// execute bash script
				shell_exec("./config-write.sh -w {$nodeId}");   // write config lines from "node{$nodeId}.conf" file to the device
			}
		}
	} else {
		$errors = [];
		$channelsData = [];
	}
	// read script output and make something with him
    shell_exec("./config-write.sh -r {$nodeId}");  // read back device config into "node{$nodeId}.read" file for populating into form
	$readFilePath = "node{$nodeId}.read";
	if (file_exists($readFilePath)) {
		$queries = file_get_contents($readFilePath);
		if ($queries !== false) {
			$queries = explode("\n", $queries);
			$channelsData = CTQueryManager::readQueriesForChannel($queries);
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>CT Setup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" media="screen" href="css/main.css" />
</head>
<body>
	<div class="top-menu">
		<a href="index.php">AIN</a>
		<a class="active-green" href="ct.php">CT</a>
		<a href="din.php">DIN</a>
		<a href="dout.php">DOUT</a>
	</div>
	<hr>
	<form method="POST" class="form-inline">
		<table class="adc-setup-table">
			<thead>
				<tr>
					<th>Input</th>
					<th>Multiplier</th>
					<th>Sensor</th>
				</tr>
			</thead>
			<tbody>
				<?php for ($i = 1; $i <= 6; $i++): ?>
					<?php if (isset($errors[$i])): ?>
						<tr>
							<td colspan="3" class="errors">
								<?php foreach ($errors[$i] as $configName => $error): ?>
									<p class="error-box"><?php echo $configName . " - " . $error; ?></p>
								<?php endforeach; ?>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<td>CT<?php echo $i; ?></td>
						<td>
							<input type="text" name="multiplier-<?php echo $i; ?>" value="<?php echo isset($channelsData[$i]["multiplier"]) ? $channelsData[$i]["multiplier"] : ""; ?>"/>
						</td>
						<td>
							<select name="sensor-<?php echo $i; ?>">
								<?php foreach ($sensorOptions as $value => $option): ?>
									<option value="<?php echo $value; ?>" <?php echo isset($channelsData[$i]["sensor"]) && $channelsData[$i]["sensor"] == $value ? "selected" : ""; ?>><?php echo $option; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endfor; ?>
				<tr>
					<td colspan="5">
						<button type="submit" class="btn">SUBMIT</button>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</body>
</html>