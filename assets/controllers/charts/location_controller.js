import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    this.element.addEventListener('chartjs:connect', this._onConnect);
  }

  disconnect() {
    this.element.removeEventListener('chartjs:connect', this._onConnect);
  }

  _onConnect(event) {
    // TODO: Handle click on chart. We could create e.g. a pretty page at
    // /images/monthly/2021/01?stars=5 or something like that? Then add the
    // URL in our stats builder and use it here as a page to visit on click?
    console.log(event.detail.chart);
    event.detail.chart.options.onClick = (mouseevent, activeElements, chart) => {
      if (activeElements.length > 0) {
        const element = activeElements[0];
        const barNumber = element.index;
        const image_index_url = chart.data.urls[barNumber];
        console.log(`Url: ${image_index_url}`);
        window.location.href = image_index_url;
      }
    };
  }
}
