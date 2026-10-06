<?php

use Mews\Purifier\Facades\Purifier;

describe('HTML Sanitization (XSS Prevention)', function () {

    it('strips script tags from lesson note content via clean()', function () {
        $malicious = '<p>Hello</p><script>alert("xss")</script><p>World</p>';
        $sanitized = clean($malicious);

        expect($sanitized)->not->toContain('<script>');
        expect($sanitized)->not->toContain('</script>');
        expect($sanitized)->toContain('<p>Hello</p>');
        expect($sanitized)->toContain('<p>World</p>');
    });

    it('strips event handler attributes from HTML content', function () {
        $malicious = '<img src="x" onerror="alert(1)"><p>Content</p>';
        $sanitized = clean($malicious);

        expect($sanitized)->not->toContain('onerror');
        expect($sanitized)->not->toContain('alert');
        expect($sanitized)->toContain('<p>Content</p>');
    });

    it('strips iframe and object tags from lesson note content', function () {
        $malicious = '<p>Safe</p><iframe src="https://evil.com"></iframe><object data="evil.swf"></object>';
        $sanitized = clean($malicious);

        expect($sanitized)->not->toContain('<iframe');
        expect($sanitized)->not->toContain('<object');
        expect($sanitized)->toContain('<p>Safe</p>');
    });

    it('preserves safe formatting tags used in lesson notes', function () {
        $safe = '<h1>Title</h1><h2>Subtitle</h2><p>Paragraph with <strong>bold</strong> and <em>italic</em>.</p><ul><li>Item 1</li><li>Item 2</li></ul><ol><li>First</li></ol><table><tr><td>Cell</td></tr></table>';
        $sanitized = clean($safe);

        expect($sanitized)->toContain('<h1>Title</h1>');
        expect($sanitized)->toContain('<h2>Subtitle</h2>');
        expect($sanitized)->toContain('<strong>bold</strong>');
        expect($sanitized)->toContain('<em>italic</em>');
        expect($sanitized)->toContain('<ul>');
        expect($sanitized)->toContain('<li>Item 1</li>');
        expect($sanitized)->toContain('<ol>');
        expect($sanitized)->toContain('<table>');
    });

    it('strips javascript: protocol from href attributes', function () {
        $malicious = '<a href="javascript:alert(1)">Click me</a>';
        $sanitized = clean($malicious);

        expect($sanitized)->not->toContain('javascript:');
    });

    it('handles null and empty content gracefully', function () {
        expect(clean(null))->toBe('');
        expect(clean(''))->toBe('');
    });
});
