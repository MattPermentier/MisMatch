function clickSuggestion(btn) {
	COLOR_INPUT[btn.dataset.ctype].setAttribute("value", btn.dataset.color);
}

const COLOR_INPUT = {
	"txt" : document.getElementById("text-color"),
	"bg" : document.getElementById("bg-color")
};

function init() {
	let colorButtons = document.querySelectorAll(".suggestion");
	for (let cbtn of colorButtons) {
		cbtn.addEventListener("click", () => clickSuggestion(cbtn));
	}
}

init();
