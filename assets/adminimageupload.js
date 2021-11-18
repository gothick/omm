import 'dropzone/dist/dropzone.css';

import { Dropzone } from "dropzone";

let myDropzone = new Dropzone("form.dropzone");

const $ = require('jquery');

$(function() {
    var percentage = parseFloat($('span.diskusagepercent').text());
    var bar = $('div.bar');
    if (percentage > 70) {
        bar.addClass('warn');
    }
    if (percentage > 90) {
        bar.addClass('critical');
    }
    bar.width(percentage + '%');
});

