const postAjax = (data) =>
  fetch(ajax.url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
    body: new URLSearchParams(data),
  }).then((r) => r.json());

export const SamoAjax = {
  townFroms: () => postAjax({ action: "bsi_samo", endpoint: "townfroms" }),
  states: (townId) => postAjax({ action: "bsi_samo", endpoint: "states", TOWNFROMINC: String(townId) }),
};
