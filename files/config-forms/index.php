<?php

	require_once("ADCQueryManager.php");

	$modeOptions = [
		"0" => "0-10V",
		"1" => "4-20mA",
		"2" => "0-20mA",
	];
	$sensorOptions = [
		"30,39" => "Current",
		"7,1" => "Humidity",
		"6,0" => "Temperature",
		"30,38" => "Voltage",
	];
	$nodeId = 4;
	if (!empty($_POST)) {
		// collect data from post and group it by adc channel
		$adcData = [];
		$errors = [];
		foreach ($_POST as $key => $value) {
			$data = explode("-", $key);
			if (count($data) !== 2 || !is_numeric($data[1]) || empty($value) && $value !== "0") {
				continue;
			}
			$configName = $data[0];
			$adcChannel = $data[1];
			// validation logic
			switch ($configName) {
				case "multiplier":
					if (empty(implode("", $value))) {
						continue 2;
					}
					foreach ($value as $configValue) {
						if (!is_numeric($configValue)) {
							$errors[$adcChannel][$configName] = "One of multiplier values is not specified or not number";
							continue 2;
						}
					}
					break;
			}
			if (!isset($adcData[$adcChannel])) {
				$adcData[$adcChannel] = [];
			}
			$adcData[$adcChannel][$data[0]] = $value;
		}
		
		if (empty($errors)) {
			$queries = [];
			foreach ($adcData as $adcId => $data) {
				$queries = array_merge($queries, ADCQueryManager::generateSetQueriesForChannel($nodeId, $adcId, $data));
			}
			if (!empty($queries)) {
				$queries = implode("\n", $queries);
				file_put_contents("node{$nodeId}.conf", $queries . "\n");
				// execute script to write config file to node
				shell_exec("./config-write.sh -w {$nodeId}");  // write config lines from "node{$nodeId}.conf" file to the device
			}
		}
	} else {
		$errors = [];
		$adcData = [];
	}
	// read script output and make something with him
    shell_exec("./config-write.sh -r {$nodeId}");  // read back device config into "node{$nodeId}.read" file for populating into form
	$readFilePath = "node{$nodeId}.read";
	if (file_exists($readFilePath)) {
		$queries = file_get_contents($readFilePath);
		if ($queries !== false) {
			$queries = explode("\n", $queries);
			$adcData = ADCQueryManager::readQueriesForChannel($queries);
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>ADC Setup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" media="screen" href="css/main.css" />
</head>
<body>
	<div class="top-menu">
		<a class="active-blue" href="index.php">AIN</a>
		<a href="ct.php">CT</a>
		<a href="din.php">DIN</a>
		<a href="dout.php">DOUT</a>
	</div>
	<hr>
	<form method="POST" class="form-inline">
		<table class="adc-setup-table">
			<thead>
				<tr>
					<th>Input</th>
					<th>Mode</th>
					<th>Multiplier slope</th>
					<th>Multiplier offset</th>
					<th>Type</th>
				</tr>
			</thead>
			<tbody>
				<?php for ($i = 1; $i <= 8; $i++): ?>
					<?php if (isset($errors[$i])): ?>
						<tr>
							<td colspan="5" class="errors">
								<?php foreach ($errors[$i] as $configName => $error): ?>
									<p class="error-box"><?php echo $configName . " - " . $error; ?></p>
								<?php endforeach; ?>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<td>AIN<?php echo $i; ?></td>
						<td>
							<select name="mode-<?php echo $i; ?>">
								<?php foreach ($modeOptions as $value => $option): ?>
									<option value="<?php echo $value; ?>" <?php echo isset($adcData[$i]["mode"]) && $adcData[$i]["mode"] == $value ? "selected" : ""; ?>><?php echo $option; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<input type="text" name="multiplier-<?php echo $i; ?>[]" value="<?php echo isset($adcData[$i]["multiplier"][0]) ? $adcData[$i]["multiplier"][0] : ""; ?>"/>
						</td>
						<td>
							<input type="text" name="multiplier-<?php echo $i; ?>[]" value="<?php echo isset($adcData[$i]["multiplier"][1]) ? $adcData[$i]["multiplier"][1] : ""; ?>"/>
						</td>
						<td>
							<select name="sensor-<?php echo $i; ?>">
								<?php foreach ($sensorOptions as $value => $option): ?>
									<option value="<?php echo $value; ?>" <?php echo isset($adcData[$i]["sensor"]) && $adcData[$i]["sensor"] == $value ? "selected" : ""; ?>><?php echo $option; ?></option>
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