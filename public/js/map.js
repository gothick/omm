// TODO: Refactor the heck out of this mess

var streetMap;
var satelliteMap;
var wgs84 = new GT_WGS84();
var base = L.latLng(51.4511364, -2.6219148);

function setUpMap(options)
{
    var mapbox_access_token = $("#mapid").data("mapboxAccessToken");
    var locus_radius = 1609.34; // 1 mile

    streetMap = L.tileLayer(
        "https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}@2x?access_token=" + mapbox_access_token,
        {
            // These are mapbox-specific
            id: "gothick/ckhb31na304g619t67r3gcngx",
            tileSize: 512,
            zoomOffset: -1,
            // More general
            maxZoom: 19,
            attribution: "Map data &copy; <a href='https://www.openstreetmap.org/'>OpenStreetMap</a> contributors, <a href='https://creativecommons.org/licenses/by-sa/2.0/'>CC-BY-SA</a>, Imagery © <a href='https://www.mapbox.com/'>Mapbox</a>"
        });

    satelliteMap = L.tileLayer(
        "https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}@2x?access_token=" + mapbox_access_token,
        {
            // These are mapbox-specific
            id: "gothick/ckhwgr59r0uai19o077hp87w4",
            tileSize: 512,
            zoomOffset: -1,
            // More general
            maxZoom: 19,
            attribution: "Map data &copy; <a href='https://www.openstreetmap.org/'>OpenStreetMap</a> contributors, <a href='https://creativecommons.org/licenses/by-sa/2.0/'>CC-BY-SA</a>, Imagery © <a href='https://www.mapbox.com/'>Mapbox</a>"
        });

    var circle = L.circle(base, {
        color: "green",
        fillColor: "#faa",
        fillOpacity: 0.15,
        radius: locus_radius,
        interactive: false
    });

    // Because Object.assign isn't supported in older browsers
    // https://stackoverflow.com/a/41455739/300836
    $.extend(options, {
        maxBounds: base.toBounds(locus_radius * 5), // Give a bit of wiggle room around the circle, but don"t let the user drift too far away
        layers: [streetMap, circle]
    });

    var map = L.map("mapid", options)
        .setView(base, 14);

    var baseMaps = {
        "Satellite": satelliteMap,
        "Streets": streetMap
    };

    L.control.layers(baseMaps).addTo(map);
    L.control.scale().addTo(map);

    L.control.locate().addTo(map);

    return map;
}

function selectedWanderStyle() {
    return {
        color: "#FFA500"
    };
}

function unselectedWanderStyle() {
    return {
        color: "#3388FF"
    };
}

var currentlySelected = null;

var CustomGeoJSON = L.GeoJSON.extend({
    options: {
        // TODO: Do we need to go to all this trouble to keep the wander id
        // handy? Maybe we could just capture it in the bindPopup closure?
        wander_id: null
    }
 });

function addAllWanders(map)
{
    // TODO: We should probably use some kind of Hydra client. This"ll do for now.
    $.getJSON("/api/wanders", function(data) {
        var last = data["hydra:totalItems"];
        $.each(data["hydra:member"], function(key, wander) {
            isLastWander = (last - 1 == key);
            var track = omnivore.gpx(wander.gpxFilename,
                    null,
                    new CustomGeoJSON(null, {
                        wander_id: wander.id,
                        style: isLastWander ? selectedWanderStyle() : unselectedWanderStyle()
                    }))
                .bindPopup(function(layer) {
                    // Toggle styles
                    currentlySelected.setStyle(unselectedWanderStyle());
                    layer.setStyle(selectedWanderStyle());
                    layer.bringToFront();
                    currentlySelected = layer;

                    addWanderImages(map, layer.options.wander_id);
                    // Popup
                    var template = "<a href='{contentUrl}'>{title}</a>";
                    return L.Util.template(template, wander);
                });
            track.wander_id = wander.id;

            track.addTo(map);
            if (isLastWander) {
                currentlySelected = track;
            }
        });
    });
}

var photoLayer = null;

function addPhotos(map, photos)
{
    if (photoLayer) {
        map.removeLayer(photoLayer);
    }

    photoLayer = L.photo.cluster().on("click", function(evt) {
        var photo = evt.layer.photo;
        var template = "<a href='{imageShowUri}'><img src='{url}' width='300' /></a><p>{caption}</p>";
        // TODO: Video
        evt.layer.bindPopup(L.Util.template(template, photo), {
            className: "leaflet-popup-photo",
            minWidth: 300
        }).openPopup();
    });

    photoLayer.add(photos).addTo(map);
}

function addWanderImages(map, wander_id) {
    var photos = [];

    // Our API allows us to grab only those photos with co-ordinates set
    $.getJSON("/api/wanders/" + wander_id + "/images?exists[latlng]=true", function(images) {
        $.each(images["hydra:member"], function(key, image) {
            photos.push({
                lat: image.latlng[0],
                lng: image.latlng[1],
                url: image.mediumImageUri,
                caption: image.title || "",
                thumbnail: image.markerImageUri,
                imageShowUri: image.imageShowUri,
                // TODO?
                video: null
            });
        });
        addPhotos(map, photos);
    });
}

function addWander(map, wander_id, add_images)
{
    $.getJSON("/api/wanders/" + wander_id, function(wander) {
        var track = omnivore.gpx(wander.gpxFilename)
            .bindPopup(function(layer) {
                return wander.title;
            })
            .addTo(map);
        // Based on the example at https://github.com/turban/Leaflet.Photo/blob/gh-pages/examples/picasa.html
        if (add_images) {
            addWanderImages(map, wander_id);
        }
    });
}
