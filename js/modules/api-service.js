// Константы
const OAUTH_TOKEN = "ddcb768f480a4d769bc960c76ac29528";
const BASE_URL = "https://online.bsigroup.ru/export/default.php";
const VERSION = "1.0";
const TYPE = "json";
const SEMO_ACTION = "api";

// Основная функция для запросов
async function makeDirectApiRequest(action, additionalParams = {}) {
  try {
    const baseParams = {
      samo_action: SEMO_ACTION,
      version: VERSION,
      type: TYPE,
      action: action,
      oauth_token: OAUTH_TOKEN,
    };

    const allParams = { ...baseParams, ...additionalParams };

    const queryString = new URLSearchParams(allParams).toString();
    const url = `${BASE_URL}?${queryString}`;

    const response = await fetch(url, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
    });

    if (!response.ok) {
      throw new Error(`HTTP Error: ${response.status}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    // Error handling without console output
    throw error;
  }
}

async function getTownFroms() {
  return makeDirectApiRequest("SearchTour_TOWNFROMS");
}

async function getStates(town_id) {
  return makeDirectApiRequest("SearchTour_STATES", { TOWNFROMINC: town_id });
}

async function getAllTours(town_id, state_id) {
  return makeDirectApiRequest("SearchTour_ALL", { TOWNFROMINC: town_id, STATEINC: state_id });
}

// Отели
async function getHotelTownFroms() {
  return makeDirectApiRequest("HotelStopsale_TOWNFROMS");
}

// Экскурсионные туры
async function getExcursionTownFrom(cur, rate) {
  return makeDirectApiRequest("SearchExcursion__TOWNFROMS");
}

// Валюты
async function getCurrencyRate(cur, rate) {
  return makeDirectApiRequest("Currency_TodayRates", {
    CURRENCY: cur,
    CURRENCYBASE: rate,
  });
}

export const APIService = {
  getTownFroms,
  getStates,
  getAllTours,
  getCurrencyRate,
  getHotelTownFroms,
  getExcursionTownFrom,
};
