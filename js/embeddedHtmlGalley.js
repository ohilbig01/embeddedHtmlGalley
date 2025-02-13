/**
 * @file plugins/generic/embeddedHtmlGalley/js/embeddedHtmlGalley.js
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Javascript for embedded Html Galleys
 */
document.addEventListener("DOMContentLoaded", function () {
	// Popup-Overlay
	let popupOverlay = document.createElement("div");
	popupOverlay.id = "image-popup-overlay";
	Object.assign(popupOverlay.style, {
		position: "fixed",
		top: "0",
		left: "0",
		width: "100vw",
		height: "100vh",
		backgroundColor: "rgba(0, 0, 0, 0.7)",
		display: "none",
		justifyContent: "center",
		alignItems: "center",
		zIndex: "1000"
	});

	// img-Element im Popup
	let popupImage = document.createElement("img");
	popupImage.id = "image-popup-img";
	Object.assign(popupImage.style, {
		maxWidth: "90%",
		maxHeight: "90%",
		boxShadow: "0 4px 8px rgba(255, 255, 255, 0.3)"
	});

	// Close Button
	let closeButton = document.createElement("span");
	closeButton.innerHTML = "&times;";
	Object.assign(closeButton.style, {
		position: "absolute",
		top: "20px",
		right: "30px",
		fontSize: "40px",
		color: "white",
		cursor: "pointer",
		fontWeight: "bold"
	});

	// Append Elements
	popupOverlay.append(popupImage, closeButton);
	document.body.appendChild(popupOverlay);

	// click images to open
	document.querySelectorAll("figure img").forEach(img => {
		img.addEventListener("click", function () {
			popupImage.src = this.src;
			popupOverlay.style.display = "flex";
		});
	});

	// to close click on close-button or background
	closeButton.addEventListener("click", () => popupOverlay.style.display = "none");
	popupOverlay.addEventListener("click", (e) => {
		if (e.target === popupOverlay) popupOverlay.style.display = "none";
	});
});

