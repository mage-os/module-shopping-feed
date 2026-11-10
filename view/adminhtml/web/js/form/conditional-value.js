define(['ko', './preserve-options', 'mage/translate'], function (ko, preserve, $t) {
    'use strict';

    return function (definition, stored, onChange) {
        var data = stored && typeof stored === 'object' && !Array.isArray(stored) ? stored : {},
            choices = definition.conditions || {},
            editor = {
                selection: !!definition.selection,
                aggregator: ko.observable(data.aggregator || 'all'),
                conditions: ko.observableArray([]),
                attributes: definition.attributes || [],
                conditionAttributes: Object.keys(choices).map(function (code) {
                    return {value: code, label: choices[code].label};
                }),
                outputTypes: [
                    {value: 'static', label: $t('Fixed text')},
                    {value: 'attribute', label: $t('Product attribute')},
                    {value: 'percent', label: $t('Percentage adjustment')}
                ]
            };

        function branch(value) {
            value = value || {};
            var result = {};
            ['type', 'value', 'attribute', 'percent'].forEach(function (key) {
                result[key] = ko.observable(value[key] === undefined ? (key === 'type' ? 'static' : '') : value[key]);
                result[key].subscribe(save);
            });
            result.attributes = preserve(editor.attributes, result.attribute());
            return result;
        }
        function plainBranch(value) {
            return {type: value.type(), value: value.value(), attribute: value.attribute(), percent: value.percent()};
        }
        function save() {
            var result = {
                aggregator: editor.aggregator(),
                conditions: editor.conditions().map(function (row) {
                    var value = {attribute: row.attribute(), operator: row.operator(), value: row.value()};
                    if (editor.selection || row.scope() !== 'product') { value.scope = row.scope(); }
                    return value;
                })
            };
            if (!editor.selection) {
                result.if_true = plainBranch(editor.ifTrue);
                result.if_false = plainBranch(editor.ifFalse);
            }
            onChange(result);
        }
        function condition(value) {
            var row = {
                scope: ko.observable(value.scope || 'product'),
                attribute: ko.observable(value.attribute || ''),
                operator: ko.observable(value.operator || '=='),
                value: ko.observable(value.value === undefined ? '' : value.value),
                chooseAttribute: $t('Choose an attribute'),
                chooseValue: $t('Choose a value')
            };
            row.attributes = preserve(editor.conditionAttributes, row.attribute());
            row.definition = ko.pureComputed(function () { return choices[row.attribute()] || {}; });
            row.operators = ko.pureComputed(function () {
                return preserve(row.definition().operators || [], row.operator());
            });
            row.options = ko.pureComputed(function () {
                return preserve(row.definition().options || [], row.value());
            });
            row.useSelect = ko.pureComputed(function () {
                return ['select', 'boolean'].indexOf(row.definition().input) !== -1 &&
                    ['==', '!='].indexOf(row.operator()) !== -1;
            });
            row.attribute.subscribe(function () {
                var operators = row.definition().operators || [];
                row.operator(operators.length ? operators[0].value : '==');
                row.value('');
                save();
            });
            row.scope.subscribe(save);
            row.operator.subscribe(save);
            row.value.subscribe(save);
            return row;
        }
        editor.ifTrue = branch(data.if_true);
        editor.ifFalse = branch(data.if_false);
        // Preserve legacy keyed rows for repair; every subsequent save emits a list.
        var storedConditions = data.conditions && typeof data.conditions === 'object' ? data.conditions : [];
        editor.conditions(Object.keys(storedConditions).map(function (key) {
            return condition(storedConditions[key] || {});
        }));
        editor.aggregator.subscribe(save);
        editor.addCondition = function () {
            if (editor.conditions().length < 50) {
                editor.conditions.push(condition({}));
                save();
            }
        };
        editor.removeCondition = function (row) {
            editor.conditions.remove(row);
            save();
        };
        return editor;
    };
});
