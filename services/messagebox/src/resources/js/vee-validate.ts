import { configure, extend } from 'vee-validate';
import i18n from './i18n/index';
import * as rules from 'vee-validate/dist/rules';

configure({
    defaultMessage: (_, values) => {
        return i18n.t(`validation.${values._rule_}`, values).toString();
    },
    mode: 'eager',
});

type RuleType = keyof typeof rules;
const ruleKeys = Object.keys(rules) as RuleType[];

ruleKeys.forEach(rule => {
    extend(rule, rules[rule]);
});
