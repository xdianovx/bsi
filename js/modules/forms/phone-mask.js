import IMask from "imask";

export const phoneMask = () => {
  const phoneInputs = document.querySelectorAll('input[type="tel"]');

  phoneInputs.forEach((item) => {
    IMask(item, {
      mask: "+{7} (000) 000 00 00",
    });
  });
};
