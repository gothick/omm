import { Controller } from '@hotwired/stimulus';

/*
 *
 */
export default class extends Controller {
  connect() {
    const submitButton = this.element.querySelector('button[type="submit"]');
    const form = this.element;
    submitButton.style.display = 'none';
    Array.from(this.element.getElementsByTagName('select')).forEach((s) => {
      s.addEventListener('change', () => {
        form.submit();
      });
    });
  }
}
