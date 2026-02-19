import MicroModal from "micromodal";

/**
 * Инициализация модального окна предупреждения
 * @param {Object} config - Конфигурация из WordPress
 * @param {boolean} config.enabled - Включена ли модалка
 * @param {string} config.message - Текст сообщения
 */
export function initMaintenanceModal(config) {
  if (!config || !config.enabled || !config.message) {
    return;
  }

  const modalId = "modal-maintenance-warning";
  const storageKey = "bsi_maintenance_modal_closed";

  // Проверяем, была ли модалка уже закрыта пользователем
  const wasClosed = localStorage.getItem(storageKey) === "true";

  if (wasClosed) {
    return;
  }

  // Обработчик закрытия модалки
  const handleClose = () => {
    localStorage.setItem(storageKey, "true");
  };

  // Слушаем событие закрытия модалки от Micromodal
  document.addEventListener("micromodal:close", (event) => {
    if (event.detail && event.detail.id === modalId) {
      handleClose();
    }
  });

  // Показываем модалку после небольшой задержки для лучшего UX
  setTimeout(() => {
    MicroModal.show(modalId);
  }, 500);
}
