var streetMap, satelliteMap, highlightWanderLayer;
var wgs84 = new GT_WGS84();
var base = L.latLng(51.4511364, -2.6219148);

function addDraggableCoordsMarker(map)
{
    return;
    // https://gis.stackexchange.com/a/124288/967
    var marker = L.marker(base, {
        // TODO: Add autoPan etc.
        draggable:true,
    });
    marker.bindPopup();

    marker.on('dragend', function(event){
        //alert('drag ended');
        var marker = event.target;
        var location = marker.getLatLng();
        wgs84.setDegrees(location.lat, location.lng);
        var popup = marker.getPopup();
        var gridref = wgs84.getOSGB().getGridRef(5);
        popup.setContent(
            location.lat.toFixed(5) + ", " + 
            location.lng.toFixed(5) + 
            "<br>" +
            gridref)
        marker.openPopup();
        });
    marker.addTo(map);   
}

function setUpMap(options = {})
{
    var mapbox_access_token = $('#mapid').data('mapboxAccessToken');
    var locus_radius = 1609.34; // 1 mile

    
    streetMap = L.tileLayer(
        'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}@2x?access_token=' + mapbox_access_token,
        {
            // These are mapbox-specific
            id: 'gothick/ckhb31na304g619t67r3gcngx',
            tileSize: 512,
            zoomOffset: -1,
            // More general
            maxZoom: 19,
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>'
        });
    
    satelliteMap = L.tileLayer(
        'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}@2x?access_token=' + mapbox_access_token,
        {
            // These are mapbox-specific
            id: 'gothick/ckhwgr59r0uai19o077hp87w4',
            tileSize: 512,
            zoomOffset: -1,
            // More general
            maxZoom: 19,
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>'
        });

    var circle = L.circle(base, {
        color: 'green',
        fillColor: '#faa',
        fillOpacity: 0.15,
        radius: locus_radius,
        interactive: false
    });
    
    options = Object.assign(
        options, 
        {
            maxBounds: base.toBounds(locus_radius * 5), // Give a bit of wiggle room around the circle, but don't let the user drift too far away
            layers: [streetMap, circle]
        }
    );

    var map = L.map('mapid', options)
        .setView(base, 14);
    
    var baseMaps = {
        "Satellite": satelliteMap,
        "Streets": streetMap
    };

    L.control.layers(baseMaps).addTo(map);
    L.control.scale().addTo(map);

    L.control.locate().addTo(map);

    // Custom layer visually to set the most recent track
    highlightWanderLayer = L.geoJson(null, {
        // http://leafletjs.com/reference.html#geojson-style
        style: function(feature) {
            return { 
                color: '#FFA500'
            };
        }
    });

    addDraggableCoordsMarker(map);
    return map;
}

function selectedWanderStyle() {
    return {
        color: '#ff0000'
    };
}

function unselectedWanderStyle() {
    return {
        color: '#3388FF'
    };
}

var currentlySelected = null;

const CustomGeoJSON = L.GeoJSON.extend({
    options: { 
        // TODO: Do we need to go to all this trouble to keep the wander id
        // handy? Maybe we could just capture it in the bindPopup closure?
        wander_id: null,
        anotherCustomProperty: 'More data!'
    }
 });

function addAllWanders(map)
{
    // TODO: We should probably use some kind of Hydra client. This'll do for now.
    $.getJSON("/api/wanders", function(data) {
        var last = data['hydra:totalItems'];
        $.each(data['hydra:member'], function(key, wander) {
            isLastWander = (last - 1 == key);
            var track = omnivore.gpx(wander.gpxFilename, 
                    null, 
                    new CustomGeoJSON(null, {
                        wander_id: wander.id
                    }))
                .bindPopup(function(layer) {
                    // Toggle styles
                    currentlySelected.setStyle(unselectedWanderStyle());
                    layer.setStyle(selectedWanderStyle());
                    currentlySelected = layer;

                    addWanderImages(map, layer.options.wander_id);
                    // Popup
                    return wander.title;
                });
            track.wander_id = wander.id;
            
            track.addTo(map);
            if (isLastWander) {
                track.bringToFront();
                track.setStyle(unselectedWanderStyle())
                currentlySelected = track;
            }
        });
    });    
}

function addPhotos(map, photos)
{
    var photoLayer = L.photo.cluster().on('click', function(evt) {
        var photo = evt.layer.photo;
        var template = '<a href="{imageEntityAdminUri}"><img src="{url}" width="300" height="300" /></a><p>{caption}</p>';
        // TODO: Video
        evt.layer.bindPopup(L.Util.template(template, photo), {
            className: 'leaflet-popup-photo',
            minWidth: 300
        }).openPopup();
    });

    photoLayer.add(photos).addTo(map);    
}

function addWanderImages(map, wander_id) {
    var photos = [];

    $.getJSON("/api/wanders/" + wander_id + "/images?exists[latlng]=true", function(images) {
        $.each(images['hydra:member'], function(key, image) {
            photos.push({
                // TODO: What if we have a photo that doesn't have a latlng?
                // Add a nice way of testing that and ignore them.
                // TODO: And then add the photos that don't have a latlng 
                // as some kind of supplemental image that can also be
                // displayed.
                lat: image.latlng[0],
                lng: image.latlng[1],
                url: image.mediumImageUri,
                caption: image.title || '',
                thumbnail: image.markerImageUri,
                imageEntityAdminUri: image.imageEntityAdminUri,
                // TODO?
                video: null
            });
        });
        addPhotos(map, photos);
    });
}

function addWander(map, wander_id, add_images = false)
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
