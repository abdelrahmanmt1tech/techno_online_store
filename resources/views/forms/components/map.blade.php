<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="mapComponent({
            state: $wire.$entangle('{{ $getStatePath() }}'),
            statePath: '{{ $getStatePath() }}',
            defaultLat: {{ $getDefaultLocation()[0] }},
            defaultLng: {{ $getDefaultLocation()[1] }},
            zoom: {{ $getZoom() }},
            showMarker: {{ $getShowMarker() ? 'true' : 'false' }},
            clickable: {{ $getClickable() ? 'true' : 'false' }},
            draggable: {{ $getDraggable() ? 'true' : 'false' }},
            tilesUrl: '{{ $getTilesUrl() ?? 'https://tile.openstreetmap.org/{z}/{x}/{y}.png' }}',
        })"
        x-init="init()"
        id="map-{{ $getStatePath() }}"
        wire:ignore
        style="min-height: 400px; border-radius: 0.5rem; overflow: hidden;"
    ></div>
</x-dynamic-component>

@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('mapComponent', (config) => ({
                map: null,
                marker: null,

                init() {
                    if (window.L === undefined) {
                        return;
                    }

                    if (!this.$wire) {
                        setTimeout(() => { if (this.$wire) this.initializeMap(); }, 100);
                        return;
                    }

                    this.initializeMap();
                },

                initializeMap() {

                    const container = this.$el;
                    const parentPath = config.statePath.replace(/\.location_map$/, '');

                    let lat = config.defaultLat;
                    let lng = config.defaultLng;

                    if (config.state && config.state.lat && config.state.lng) {
                        lat = config.state.lat;
                        lng = config.state.lng;
                    } else {
                        const existingLat = this.$wire.get(parentPath + '.latitude');
                        const existingLng = this.$wire.get(parentPath + '.longitude');
                        if (existingLat && existingLng) {
                            lat = existingLat;
                            lng = existingLng;
                            config.state = { lat, lng };
                        }
                    }

                    this.map = L.map(container, {
                        center: [lat, lng],
                        zoom: config.zoom,
                        zoomControl: true,
                    });

                    L.tileLayer(config.tilesUrl, {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
                    }).addTo(this.map);

                    if (config.showMarker) {
                        this.marker = L.marker([lat, lng], {
                            draggable: config.draggable,
                        }).addTo(this.map);

                        this.syncHidden(lat, lng);
                    }

                    if (config.clickable) {
                        this.map.on('click', (e) => {
                            const { lat, lng } = e.latlng;
                            if (this.marker) {
                                this.marker.setLatLng([lat, lng]);
                            }
                            this.syncState(lat, lng);
                        });
                    }

                    if (config.draggable && this.marker) {
                        this.marker.on('dragend', (e) => {
                            const { lat, lng } = e.target.getLatLng();
                            this.syncState(lat, lng);
                        });
                    }

                    setTimeout(() => this.map?.invalidateSize(), 200);
                },

                syncState(lat, lng) {
                    config.state = { lat, lng };
                    this.syncHidden(lat, lng);
                },

                syncHidden(lat, lng) {
                    if (!this.$wire) {
                        return;
                    }
                    const parentPath = config.statePath.replace(/\.location_map$/, '');
                    this.$wire.set(parentPath + '.latitude', String(lat), true);
                    this.$wire.set(parentPath + '.longitude', String(lng), true);
                },
            }));
        });
    </script>
@endonce
