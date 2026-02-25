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
  const HIDE_DURATION_MS = 15 * 60 * 1000; // 15 минут

  // Проверяем, закрывал ли пользователь модалку недавно (в течение 15 минут)
  const closedAt = Number(localStorage.getItem(storageKey));
  const isWithinCooldown =
    Number.isFinite(closedAt) && Date.now() - closedAt < HIDE_DURATION_MS;

  if (isWithinCooldown) {
    return;
  }

  // Обработчик закрытия модалки — сохраняем время закрытия (Micromodal не диспатчит событие, только onClose)
  const handleClose = () => {
    localStorage.setItem(storageKey, String(Date.now()));
  };

  // Показываем модалку после небольшой задержки для лучшего UX; onClose вызывается при закрытии
  setTimeout(() => {
    MicroModal.show(modalId, {
      onClose: handleClose,
    });
  }, 500);
}
