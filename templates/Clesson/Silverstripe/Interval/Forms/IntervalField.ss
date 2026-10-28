<div class="interval-field__container" data-interval-field="$ID" data-labels="$LabelsJson.ATT" style="min-height: 80px;">
    <!-- Display Mode: Shows formatted interval -->
    <div class="interval-field__display" style="display: none; cursor: pointer; background: #fff; border: 1px solid #ced4da; border-radius: 4px; padding: 0.375rem 0.75rem; line-height: 1.5; min-height: 2.375rem;">
        <span class="interval-field__formatted" style="color: #0d6efd; text-decoration: underline;">$FormattedValue</span>
    </div>

    <!-- Edit Mode: Shows input fields -->
    <div class="interval-field__edit" style="display: flex; flex-wrap: wrap; gap: 1rem;">
        <% loop $UnitFields %>
            <div style="flex: 0 0 auto; display: flex; flex-direction: column;">
                <label for="$ID" style="margin-bottom: 0.25rem; font-size: 0.85rem; font-weight: 500;">$Label</label>
                <input type="number" id="$ID" name="$Name" value="$Value" min="0" style="width: 60px; padding: 0.25rem 0.5rem;" class="interval-field__input" data-unit="$Unit" />
            </div>
        <% end_loop %>
    </div>
</div>

<script type="text/javascript">
(function() {
    function formatInterval(values, labels) {
        var nonZero = labels.units.filter(function(u) { return values[u] > 0; });
        if (nonZero.length === 0) return '';

        if (nonZero.length === 1 && values[nonZero[0]] === 1) {
            return labels.adverbs[nonZero[0]];
        }

        var parts = nonZero.map(function(u) {
            var count = values[u];
            var label = count === 1 ? labels.singular[u] : labels.plural[u];
            return count + ' ' + label;
        });

        var interval;
        if (parts.length === 1) {
            interval = parts[0];
        } else {
            var last = parts.pop();
            interval = parts.join(', ') + ' ' + labels.and + ' ' + last;
        }

        return labels.prefix.replace('{interval}', interval);
    }

    function setupIntervalField(container) {
        var labels = JSON.parse(container.getAttribute('data-labels') || '{}');
        var displayMode = container.querySelector('.interval-field__display');
        var editMode = container.querySelector('.interval-field__edit');
        var inputs = container.querySelectorAll('.interval-field__input');
        var formatted = container.querySelector('.interval-field__formatted');

        if (!displayMode || !editMode) return;

        function getValues() {
            var values = {};
            inputs.forEach(function(input) {
                values[input.getAttribute('data-unit')] = parseInt(input.value) || 0;
            });
            return values;
        }

        function hasValue(values) {
            return Object.values(values).some(function(v) { return v > 0; });
        }

        function updateDisplay() {
            var values = getValues();
            if (hasValue(values)) {
                formatted.textContent = formatInterval(values, labels);
                displayMode.style.display = 'block';
                editMode.style.display = 'none';
            } else {
                displayMode.style.display = 'none';
                editMode.style.display = 'flex';
            }
        }

        displayMode.addEventListener('click', function(e) {
            e.preventDefault();
            displayMode.style.display = 'none';
            editMode.style.display = 'flex';
            inputs[0].focus();
        });

        container.addEventListener('focusout', function(e) {
            if (!container.contains(e.relatedTarget)) {
                updateDisplay();
            }
        });

        updateDisplay();
    }

    document.querySelectorAll('[data-interval-field]').forEach(setupIntervalField);
})();
</script>
