<?php

	require_once("DINQueryManager.php");

	$modeOptions = [
		DINQueryManager::MODE_DISABLE => "Disable",
		DINQueryManager::MODE_STATUS => "Status",
		DINQueryManager::MODE_COUNTER => "Counter",
    ];
    $sensorOptions = [
		"13,18" => "kWh",
		"21,35" => "Volume",
    ];
	$nodes = [ 1, 2 ];
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
            $channel = $data[1];
            // validation logic
			switch ($configName) {
				case "multiplier":
					if (empty(implode("", $value))) {
						continue 2;
					}
					foreach ($value as $configValue) {
						if (!is_numeric($configValue)) {
							$errors[$channel][$configName] = "One of multiplier values is not specified or not number";
							continue 2;
						}
					}
					break;
			}
			if (!isset($channelsData[$channel])) {
				$channelsData[$channel] = [];
			}
			$channelsData[$channel][$data[0]] = $value;
		}
		
		if (empty($errors)) {
			$queries = [];
			foreach ($channelsData as $channelId => $data) {
                $channelQueries = DINQueryManager::generateSetQueriesForChannel(0, $channelId, $data);
				foreach ($channelQueries as $nodeID => $nodeQueries) {
                    if (!isset($queries[$nodeID])) {
                        $queries[$nodeID] = $nodeQueries;
                    } else {
                        $queries[$nodeID] = array_merge($queries[$nodeID], $nodeQueries);
                    }
                }
            }
			if (!empty($queries)) {
                foreach ($queries as $nodeID => $nodeQueries) {
					$nodeQueries = implode("\n", $nodeQueries);
                    file_put_contents("node{$nodeID}.conf", $nodeQueries . "\n");
                    // execute bash script for each node
                    shell_exec("./config-write.sh -w {$nodeID}");   // write config lines from "node{$nodeId}.conf" file to the device
                }
			}
		}
	} else {
		$errors = [];
		$channelsData = [];
	}
    // read script output and make something with him
    $queries = [];
    foreach ($nodes as $nodeID) {
        shell_exec("./config-write.sh -r {$nodeID}");  // read back device config into "node{$nodeId}.read" file for populating into form
        $readFilePath = "node{$nodeID}.read";
        if (file_exists($readFilePath)) {
            $fileContent = file_get_contents($readFilePath);
            if ($fileContent !== false) {
                $queries = array_merge($queries, explode("\n", $fileContent));
            }
        }
    }
    if (!empty($queries)) {
        $channelsData = DINQueryManager::readQueriesForChannel($queries);
    }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>DIN Setup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" media="screen" href="css/main.css" />
</head>
<body>
	<div class="top-menu">
		<a href="index.php">AIN</a>
		<a href="ct.php">CT</a>
		<a class="active-yellow" href="din.php">DIN</a>
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
				<?php for ($i = 1; $i <= 16; $i++): ?>
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
						<td>DIN<?php echo str_pad($i, 2, "0", STR_PAD_LEFT); ?></td>
						<td>
							<select name="mode-<?php echo $i; ?>">
								<?php foreach ($modeOptions as $value => $option): ?>
									<option value="<?php echo $value; ?>" <?php echo isset($channelsData[$i]["mode"]) && $channelsData[$i]["mode"] == $value ? "selected" : ""; ?>><?php echo $option; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<input type="text" name="multiplier-<?php echo $i; ?>[]" value="<?php echo isset($channelsData[$i]["multiplier"][0]) ? $channelsData[$i]["multiplier"][0] : ""; ?>"/>
						</td>
						<td>
							<input type="text" name="multiplier-<?php echo $i; ?>[]" value="<?php echo isset($channelsData[$i]["multiplier"][1]) ? $channelsData[$i]["multiplier"][1] : ""; ?>"/>
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