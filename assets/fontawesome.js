// https://fontawesome.com/v5.15/how-to-use/on-the-web/advanced/svg-javascript-core
import {
    library,
    dom
} from '@fortawesome/fontawesome-svg-core';
// The Sort icons are for knpPaginator's bootstrap_v5_fa_sortable_link.html.twig
import {
    faPlus,
    faMapMarker,
    faInfoCircle,
    faExclamationCircle,
    faSort,
    faSortUp,
    faSortDown
} from '@fortawesome/free-solid-svg-icons';

library.add(
    faPlus,
    faMapMarker,
    faInfoCircle,
    faExclamationCircle,
    faSort,
    faSortUp,
    faSortDown
);
dom.watch();
