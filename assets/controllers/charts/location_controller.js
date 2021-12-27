import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    this.element.addEventListener('chartjs:connect', this._onConnect);
  }

  disconnect() {
    this.element.removeEventListener('chartjs:connect', this._onConnect);
  }

  _onConnect(event) {
    // Each bar of our bargraph has an associated URL that links to the
    // image filtering system to show all the images that fit the same
    // criteria. Take us there on a click.
    event.detail.chart.options.onClick = (mouseevent, activeElements, chart) => {
      if (activeElements.length > 0) {
        const element = activeElements[0];
        const barNumber = element.index;
        const imageIndexUrl = chart.data.urls[barNumber];
        window.location.href = imageIndexUrl;
      }
    };
    // Make sure the bars seem clickable by giving a clicky pointer on hover
    event.detail.chart.options.onHover = (mouseevent, activeElements) => {
      event.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
    };
  }
}
