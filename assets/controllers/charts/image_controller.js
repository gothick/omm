import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    this.element.addEventListener('chartjs:connect', this._onConnect);
  }

  disconnect() {
    this.element.removeEventListener('chartjs:connect', this._onConnect);
  }

  _onConnect(event) {
    // Each segment of our stacked bargraph has an associated URL that
    // links to the image filtering system to show all the images that
    // fit the same criteria. Take us there on a click.
    event.detail.chart.options.onClick = (_mouseevent, activeElements, chart) => {
      if (activeElements.length > 0) {
        const element = activeElements[0];
        const starRating = element.datasetIndex;
        const barNumber = element.index;
        const imageIndexUrl = chart.data.urls[starRating][barNumber];
        window.location.href = imageIndexUrl;
      }
    };
    // Make sure the bars seem clickable by giving a clicky pointer on hover
    event.detail.chart.options.onHover = (_mouseevent, activeElements, chart) => {
      chart.canvas.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
    };
  }
}
