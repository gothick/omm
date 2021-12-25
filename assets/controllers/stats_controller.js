import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    this.element.addEventListener('chartjs:connect', this._onConnect);
  }

  disconnect() {
    this.element.removeEventListener('chartjs:connect', this._onConnect);
  }

  _onConnect(event) {
    // console.log(event.detail.chart);
    event.detail.chart.options.onClick = (mouseevent) => {
      // TODO: Handle click on chart.
    };
  }
}
