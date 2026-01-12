// /modules/dropdown.js
export function dropdown(containerSelector, options = {}) {
  const container = typeof containerSelector === "string" ? document.querySelector(containerSelector) : containerSelector;
  if (!container) return null;

  const trigger = container.querySelector(options.triggerSelector || ".js-dropdown-trigger");
  const panel = container.querySelector(options.panelSelector || ".js-dropdown-panel");
  if (!trigger || !panel) return null;

  let isOpen = false;

  function handleTriggerClick(event) {
    event.preventDefault();
    toggle();
  }

  function handleDocumentClick(event) {
    if (!container.contains(event.target)) {
      close();
    }
  }

  function handleKeyDown(event) {
    if (event.key === "Escape") {
      close();
    }
  }

  function open() {
    if (isOpen) return;
    isOpen = true;
    container.classList.add("is-open");
    document.addEventListener("click", handleDocumentClick);
    document.addEventListener("keydown", handleKeyDown);
  }

  function close() {
    if (!isOpen) return;
    isOpen = false;
    container.classList.remove("is-open");
    document.removeEventListener("click", handleDocumentClick);
    document.removeEventListener("keydown", handleKeyDown);
  }

  function toggle() {
    isOpen ? close() : open();
  }

  trigger.addEventListener("click", handleTriggerClick);

  return { open, close, toggle };
}
