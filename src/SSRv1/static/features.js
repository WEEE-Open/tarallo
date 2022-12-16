if (window.featureMaps === undefined) {
	window.featureMaps = new Promise((resolve, reject) => {
		let featureNames = new Map();
		let featureExplainers = new Map();
		let featureTypes = new Map();
		let featureValues = new Map();
		let featureValuesTranslated = new Map();
		let featureDefaultsItems = new Map();
		let featureDefaultsProducts = new Map();

		// Get the giant JSON
		let response = fetch('/features.json', {
			headers: {
				'Accept': 'application/json'
			},
			method: 'GET',
			credentials: 'include',
		}).then(r => {
			if (!r.ok) {
				reject(`Error while fetching features.json: HTTP ${r.status} ${r.statusText}`);
			}

			return r.json();
		}).then(payload => {
			// Rebuild the Maps. These were previously precomputed.
			let features = payload["features"];
			for (let group of Object.keys(features)) { // Keys are group IDs
				let featuresInGroup = features[group]; // Features from a group
				for (let feature of featuresInGroup) { // For each feature, build Maps
					featureTypes.set(feature.name, feature.type);
					// noinspection JSUnresolvedVariable
					featureNames.set(feature.name, feature.printableName);
					// noinspection JSUnresolvedVariable
					if (feature.type === 'e') {
						featureValues.set(feature.name, Object.keys(feature.values));
						featureValuesTranslated.set(feature.name, Object.values(feature.values));
					}
				}
			}

			let explanations = payload["explains"];
			for (let feature of Object.keys(explanations)) {
				featureExplainers.set(feature, explanations[feature])
			}

			let defaults = payload["defaults"];
			for (let type of Object.keys(defaults)) { // Keys are types
				featureDefaultsItems.set(type, defaults[type]) // Value is a list of feature names
			}

			// Same as above
			defaults = payload["products"];
			for (let type of Object.keys(defaults)) {
				featureDefaultsProducts.set(type, defaults[type])
			}
		}).then(() => {
			resolve({featureNames, featureExplainers, featureTypes, featureValues, featureValuesTranslated, featureDefaultsItems, featureDefaultsProducts});
		});
	});
}
