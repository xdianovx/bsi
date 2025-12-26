export const initSingleEducationPrograms = () => {
  const root = document.querySelector(".js-education-programs");
  if (!root) return;

  const filters = root.querySelector(".js-education-programs-filters");
  const list = root.querySelector(".js-education-programs-list");
  if (!filters || !list) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const educationId = parseInt(root.closest(".single-education")?.dataset?.educationId || "0", 10) || 
                       parseInt(document.querySelector('[data-education-id]')?.getAttribute("data-education-id") || "0", 10);
  
  if (!educationId) {
    return;
  }

  const ageMinInput = filters.querySelector('input[name="program_age_min"]');
  const ageMaxInput = filters.querySelector('input[name="program_age_max"]');
  const durationMinInput = filters.querySelector('input[name="program_duration_min"]');
  const durationMaxInput = filters.querySelector('input[name="program_duration_max"]');
  const dateInput = filters.querySelector('input[name="program_date"]');

  const setLoading = (on) => root.classList.toggle("is-loading", !!on);

  const loadPrograms = async () => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "education_programs_by_school");
      body.set("education_id", String(educationId));

      if (ageMinInput && ageMinInput.value) {
        body.set("program_age_min", ageMinInput.value);
      }
      if (ageMaxInput && ageMaxInput.value) {
        body.set("program_age_max", ageMaxInput.value);
      }

      if (durationMinInput && durationMinInput.value) {
        body.set("program_duration_min", durationMinInput.value);
      }
      if (durationMaxInput && durationMaxInput.value) {
        body.set("program_duration_max", durationMaxInput.value);
      }

      if (dateInput && dateInput.value) {
        body.set("program_date", dateInput.value);
      }

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      list.innerHTML = json.data.html || "";
    } catch (e) {
      // Error handling without console output
    } finally {
      setLoading(false);
    }
  };

  if (ageMinInput) {
    ageMinInput.addEventListener("change", loadPrograms);
  }
  if (ageMaxInput) {
    ageMaxInput.addEventListener("change", loadPrograms);
  }

  if (durationMinInput) {
    durationMinInput.addEventListener("change", loadPrograms);
  }
  if (durationMaxInput) {
    durationMaxInput.addEventListener("change", loadPrograms);
  }

  if (dateInput) {
    dateInput.addEventListener("change", loadPrograms);
  }
};

