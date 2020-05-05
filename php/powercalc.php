<?php

/*

// Thanks to: https://www.omnicalculator.com/sports/cycling-wattage

P = (Fg + Fr + Fa) * v / (1 - loss)

- P is your power,
- Fg is the resisting force due to gravity,
- Fr is the rolling resistance force,
- Fa is the aerodynamic drag,
- v is your speed in m/s,
- loss is the percentage loss in power.

Fg = g * sin(arctan(slope)) * (M + m)

- Fg is the resisting force due to gravity,
- g is the gravitational acceleration, equal to 9.80655 m/s²,
- slope is the slope of the hill, expressed as a percentage (positive for going uphill and negative for going downhill),
- M is your weight in kg,
- m is the weight of your bicycle and any extra gear, also in kg.

Fr = g * cos(arctan(slope)) * (M + m) * Crr

https://i.gyazo.com/9fbf8cc6ff60759134d72d2b0ade1ce6.png

- Fr is the rolling resistance,
- g is the gravitational acceleration, equal to 9.80655 m/s²,
- slope is the slope of the hill, expressed as a percentage (positive for going uphill and negative for going downhill),
- M is your weight in kg,
- m is the weight of your bicycle and any extra gear in kg,
- Crr is the rolling resistance coefficient.

Fa = 0.5 * Cd * A * ρ * (v + w)²

https://i.gyazo.com/7c4d49539ae8116fff76f84537611213.png

- Fa is the aerodynamic drag,
- Cd is the drag coefficient,
- A is your frontal area,
- ρ is the air density,
- v is your speed,
- w is the wind speed (positive for head wind and negative for tail wind).

ρ = ρₒ * exp[(-g * Mₒ * h) / (R * Tₒ)]

- ρ is the air density,
- ρₒ is the air density at the sea level, equal to 1.225 kg/m³,
- g is the gravitational acceleration, equal to 9.80655 m/s²,
- Mₒ is the molar mass of Earth's air, equal to 0.0289644 kg/mol,
- h is the elevation above sea level,
- R is the universal gas constant for air, equal to 8.3144598 N·m/(mol·K),
- Tₒ is the standard temperature equal to 288.15 K.

ρ = 1.225 * exp(-0.00011856 * h)

Power losses:

- 3% for a new, well-oiled chain;
- 4% for a dry chain (for example, when the oil has been washed away by rain);
- 5% for a dry chain that is so old it became elongated.

Calories = ((Power * Time) / 4.18 ) / 0.24

*/

function calc($data) {
	$vel = $data["distance"] * 1000 / $data["time"];
	$loss = 0.04;

	$rdata = array();

	// Fg = g * sin(arctan(slope)) * (M + m)

	$g = 9.80655;
	$slope = $data["ramp"] / ($data["distance"] * 1000); // Needs more precision (stages)

	$Mass = 74;
	$m = 15;

	$fg = $g * sin(atan($slope)) * ($Mass + $m);

	// Fr = g * cos(arctan(slope)) * (M + m) * Crr

	$fr = $g * cos(atan($slope)) * ($Mass + $m) * 0.0063; // % of each stage (off-road, asphalt...)

	// Fa = 0.5 * Cd * A * ρ * (v + w)²

	$cdA = 0.324;
	$w = 10 / 3.6;
	$p = 1.225 * exp(-0.00011856 * $data["ramp"]);
	$fa = 0.5 * $cdA * $p * pow($vel + $w, 2);

	// $satan = sin(atan($slope));
	// $pow = pow($vel + $w, 2);

	// echo "slope: $slope<br>satan: $satan<br>fg: $fg<br>fr: $fr<br>fa: $fa<br>vel: $vel<br>";
	// echo "fg: $fg<br>fr: $fr<br>fa: $fa<br>vel: $vel<br>";
	// echo "cda: $cdA<br>p: $p<br>pow: $pow<br>";

	// P = (Fg + Fr + Fa) * v / (1 - loss)

	$p = ($fg + $fr + $fa) * $vel / (1 - $loss);

	// Cal = ((Power * Time) / 4.18 ) / 0.24

	$joules = $p * $data["time"] / 0.24;
	$cal = $joules / 4184;
	// (($p * $data["time"]) / 4.18) * 0.24;

	$wperkg = $p / $Mass;

	$rdata["power"] = round($p, 2);
	$rdata["kcal"] = round($cal, 2);
	$rdata["velocity"] = round($vel * 3.6, 2);
	$rdata["joules"] = round($joules, 2);
	$rdata["wperkg"] = round($wperkg, 2);

	return $rdata;
}