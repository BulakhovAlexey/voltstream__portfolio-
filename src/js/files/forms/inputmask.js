/* Полевые маски (на работе) */

// Соединение функциональности «Frilation» Frilater
// Соединение списка активных модулей
import { flsModules } from "../modules.js";

// Модульное соединение
import "inputmask/dist/inputmask.min.js";

const inputMasks = document.querySelectorAll("input");
if (inputMasks.length) {
  flsModules.inputmask = Inputmask().mask(inputMasks);
}
