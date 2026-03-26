// Project-wide JavaScript entry point.

(function () {
	function initTables() {
		if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
			return;
		}

		window.jQuery('table.js-paginated-table').each(function () {
			var tableElement = window.jQuery(this);
			if (!tableElement.parent().hasClass('table-responsive')) {
				tableElement.wrap('<div class="table-responsive"></div>');
			}

			if (window.jQuery.fn.DataTable.isDataTable(this)) {
				return;
			}

			tableElement.DataTable({
				order: [],
				pageLength: 10,
				lengthMenu: [[10, 25, 50], [10, 25, 50]]
			});
		});
	}

	function enhanceTableActionIcons() {
		var iconMap = {
			view: 'bi-eye',
			edit: 'bi-pencil-square',
			delete: 'bi-trash',
			del: 'bi-trash',
			save: 'bi-floppy'
		};

		document.querySelectorAll('button.btn, a.btn').forEach(function (element) {
			if (element.querySelector('svg, i.bi')) {
				return;
			}

			var label = (element.textContent || '').trim().toLowerCase();
			var iconClass = iconMap[label];
			if (!iconClass) {
				return;
			}

			var readableLabel = label === 'del' ? 'Delete' : (label.charAt(0).toUpperCase() + label.slice(1));
			element.innerHTML = '<i class="bi ' + iconClass + '" aria-hidden="true"></i><span class="visually-hidden">' + readableLabel + '</span>';
			element.setAttribute('title', readableLabel);
			element.setAttribute('aria-label', readableLabel);
		});
	}

	initTables();
	enhanceTableActionIcons();

	var weatherSection = document.getElementById('weather-section');
	var weatherContainer = document.getElementById('weather-forecast');

	if (!weatherSection || !weatherContainer) {
		return;
	}

	var endpoint = weatherSection.getAttribute('data-endpoint');
	if (!endpoint) {
		weatherContainer.innerHTML = '<p class="mb-0 text-danger">Weather endpoint is not configured.</p>';
		return;
	}

	var iconMap = {
		"Clear": "☀️",
		"Clouds": "☁️",
		"Rain": "🌧️",
		"Drizzle": "🌦️",
		"Thunderstorm": "⛈️",
		"Snow": "❄️",
		"Mist": "🌫️",
		"Fog": "🌫️",
		"Haze": "🌫️"
	};

	function escapeHtml(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	function formatForecastDateTime(dtTxt) {
		if (!dtTxt) {
			return '';
		}

		var parts = dtTxt.split(' ');
		if (parts.length !== 2) {
			return dtTxt;
		}

		var datePart = parts[0];
		var timeBits = parts[1].split(':');
		if (timeBits.length < 2) {
			return dtTxt;
		}

		var hour24 = parseInt(timeBits[0], 10);
		var minute = timeBits[1];
		if (isNaN(hour24)) {
			return dtTxt;
		}

		var suffix = hour24 >= 12 ? 'PM' : 'AM';
		var hour12 = hour24 % 12;
		if (hour12 === 0) {
			hour12 = 12;
		}

		return datePart + ' ' + hour12 + ':' + minute + ' ' + suffix;
	}

	function renderForecast(payload) {
		if (!payload || !Array.isArray(payload.list) || payload.list.length === 0) {
			weatherContainer.innerHTML = '<p class="mb-0 text-muted">No forecast data available.</p>';
			return;
		}

		var cityText = payload.city ? (payload.city + (payload.country ? ', ' + payload.country : '')) : 'Selected location';
		var cardsHtml = payload.list.map(function (entry, index) {
			var category = entry.weather && entry.weather[0] ? entry.weather[0].main : "Unknown";
			var description = entry.weather && entry.weather[0] ? entry.weather[0].description : "";
			var icon = iconMap[category] || "🌡️";
			var temp = entry.main && typeof entry.main.temp === 'number' ? entry.main.temp.toFixed(1) : 'N/A';
			var dateLabel = formatForecastDateTime(entry.dt_txt || '');
			var humidity = entry.main && typeof entry.main.humidity === 'number' ? entry.main.humidity : null;
			var wind = entry.wind && typeof entry.wind.speed === 'number' ? entry.wind.speed.toFixed(1) : null;
			var pressure = entry.main && typeof entry.main.pressure === 'number' ? entry.main.pressure : null;
			var detailHtml = ''
				+ '<div><strong>Condition:</strong> ' + escapeHtml(category) + '</div>'
				+ '<div><strong>Humidity:</strong> ' + (humidity === null ? 'N/A' : escapeHtml(String(humidity)) + '%') + '</div>'
				+ '<div><strong>Wind:</strong> ' + (wind === null ? 'N/A' : escapeHtml(String(wind)) + ' m/s') + '</div>'
				+ '<div><strong>Pressure:</strong> ' + (pressure === null ? 'N/A' : escapeHtml(String(pressure)) + ' hPa') + '</div>';

			return ''
				+ '<article class="weather-card" data-weather-card>'
				+ '<button type="button" class="weather-card-toggle" data-weather-tooltip="' + encodeURIComponent(detailHtml) + '">'
				+ '<div class="weather-icon">' + icon + '</div>'
				+ '<div class="weather-time">' + escapeHtml(dateLabel) + '</div>'
				+ '<div class="weather-temp">' + escapeHtml(temp) + '°C</div>'
				+ '<div class="weather-category">' + escapeHtml(description || category) + '</div>'
				+ '</button>'
				+ '</article>';
		}).join('');

		weatherContainer.innerHTML = ''
			+ '<p class="weather-meta">Forecast for ' + escapeHtml(cityText) + '</p>'
			+ '<div class="weather-scroll" role="list">' + cardsHtml + '</div>';

		bindWeatherTooltip();
	}

	function bindWeatherTooltip() {
		var toggles = weatherContainer.querySelectorAll('.weather-card-toggle[data-weather-tooltip]');
		if (!toggles.length) {
			return;
		}

		var tooltip = document.getElementById('weather-global-tooltip');
		if (!tooltip) {
			tooltip = document.createElement('div');
			tooltip.id = 'weather-global-tooltip';
			tooltip.className = 'weather-tooltip';
			document.body.appendChild(tooltip);
		}

		var activeToggle = null;

		function positionTooltip(target) {
			if (!target) {
				return;
			}

			var rect = target.getBoundingClientRect();
			var tooltipRect = tooltip.getBoundingClientRect();
			var spacing = 10;
			var top = rect.top - tooltipRect.height - spacing;
			if (top < 8) {
				top = rect.bottom + spacing;
			}

			var left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
			var maxLeft = window.innerWidth - tooltipRect.width - 8;
			if (left < 8) {
				left = 8;
			}
			if (left > maxLeft) {
				left = maxLeft;
			}

			tooltip.style.top = Math.round(top) + 'px';
			tooltip.style.left = Math.round(left) + 'px';
		}

		function showTooltip(toggle) {
			activeToggle = toggle;
			tooltip.innerHTML = decodeURIComponent(toggle.getAttribute('data-weather-tooltip') || '');
			tooltip.classList.add('is-visible');
			positionTooltip(toggle);
		}

		function hideTooltip() {
			activeToggle = null;
			tooltip.classList.remove('is-visible');
		}

		toggles.forEach(function (toggle) {
			toggle.addEventListener('mouseenter', function () {
				showTooltip(toggle);
			});

			toggle.addEventListener('mouseleave', hideTooltip);

			toggle.addEventListener('focus', function () {
				showTooltip(toggle);
			});

			toggle.addEventListener('blur', hideTooltip);
		});

		window.addEventListener('scroll', function () {
			if (activeToggle) {
				positionTooltip(activeToggle);
			}
		}, { passive: true });

		window.addEventListener('resize', function () {
			if (activeToggle) {
				positionTooltip(activeToggle);
			}
		});
	}

	function loadForecast(lat, lon) {
		var url = endpoint + '?lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lon);
		fetch(url)
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				if (!payload || payload.ok !== true) {
					var message = payload && payload.message ? payload.message : 'Unable to load weather forecast.';
					weatherContainer.innerHTML = '<p class="mb-0 text-danger">' + escapeHtml(message) + '</p>';
					return;
				}

				renderForecast(payload);
			})
			.catch(function () {
				weatherContainer.innerHTML = '<p class="mb-0 text-danger">Unable to load weather forecast.</p>';
			});
	}

	function loadWithFallback() {
		// UK fallback coordinates when location access is denied/unavailable.
		loadForecast(55.3781, -3.4360);
	}

	if (!navigator.geolocation) {
		loadWithFallback();
		return;
	}

	navigator.geolocation.getCurrentPosition(
		function (position) {
			loadForecast(position.coords.latitude, position.coords.longitude);
		},
		function () {
			loadWithFallback();
		},
		{
			enableHighAccuracy: false,
			timeout: 10000,
			maximumAge: 300000
		}
	);
})();
