# Activity bot — STTShoutbox 2.4.0

[Polska instrukcja](BOT-AKTYWNOSCI.md) · [Back to README](../README.md)

Open **Admin CP → Configuration → Plugins → STTShoutbox → Activity bot**. A link is also available in Quick setup. You need permission to change MyBB settings.

The panel controls notifications about forum activity. It does not moderate or remove forum posts, disable thank-you/unlock plugins, or filter what people write directly in the shoutbox.

## Choose what the bot announces

The main bot switch controls activity notifications. Separate switches control new topics, replies and registrations. New topics and replies are enabled by default; registrations are disabled. Forum exclusions also apply to subforums. Real cards are visible only to readers with access to the relevant forum, and password-protected forums remain excluded.

## Filter defaults

| Setting | Default | Effect |
| --- | --- | --- |
| Filter thank-you phrases | On | Skip activity matching the configured phrase rule. |
| Phrase matching | Contains a phrase | Match a complete word or phrase anywhere in the author's prose. |
| Phrase list | `dzięki`, `dziękuję`, `dziękujemy`, `thanks`, `thank you`, `thx` | One phrase per line; the list is editable. |
| Filter short posts | On | Skip activity with fewer words than the minimum. |
| Minimum words | 10 | Nine words are skipped; exactly ten pass this filter. |
| Scope | Replies | New-topic first posts are excluded from these content filters unless you choose both. |
| Link/attachment exemption | Off | When enabled, a link or attachment (including one not embedded in the post) can bypass the short-post filter only. |
| Apply filters to existing cards | On | Apply current rules when showing previously recorded activity. |

The two filters are independent. If either active rule rejects a post, its activity notification is skipped. A post with ten or more words can still be skipped by the thank-you filter. The optional link/attachment exemption does not bypass the thank-you filter, bot switches, exclusions or forum permissions.

## Phrase matching

Matching ignores letter case and Polish accents: `DZIĘKI`, `dzieki` and `dzięki` match the same phrase. Complete words and phrases are matched; a word containing the same letters as a substring is not a match. URL addresses and quoted material are not searched as the author's prose.

In **contains a phrase** mode, `Dzięki, ale link już nie działa` matches because it contains the word `dzięki`. This is the default and may hide otherwise useful replies that include a thank-you. Choose **acknowledgement-only** if you want substantive replies containing thanks to remain eligible. That mode recognises short acknowledgements such as `Dziękuję bardzo` and common Polish/English variants rather than any occurrence in a longer response.

The list accepts up to 100 phrases, 80 characters per phrase and 4,000 characters in total. Use one phrase per line. Use the tester after editing it. Avoid ambiguous short words such as `ty`: this is an ordinary Polish pronoun as well as an English abbreviation for thank you.

## How words are counted

The bot evaluates the author's text after removing presentation markup:

- Quoted blocks, including nested quotes, do not inflate the word count or trigger thank-you matching.
- MyCode formatting tags and their attributes do not count as words.
- URLs, code blocks and media are not counted as prose.
- Text inside hide tags is counted; hiding a contribution does not make its body empty for this check.
- Emoji and punctuation do not become extra words.

The minimum is adjustable from 1 to 100. Turn the short-post filter off to allow any length. Visible link captions can contribute words; the destination address does not.

Short contributions containing a link or an attachment can use the optional exemption. An embedded image or other media block is not itself a prose contribution. The tester includes an attachment option to model an attachment (including one not embedded in the post).

## Test a sample before saving

Set the switches and values in the form, choose the sample event type, and enter sample text. Run the tester to see the decision, word count and reasons. It uses **the current form values, including unsaved changes**. Testing does not save settings, create a forum post or send a chat message.

The tester respects the bot and notification-type switches. Its result describes the content rules for your sample. Actual activity cards additionally enforce forum exclusions, moderation visibility and each reader's permissions.

With the default settings:

| Sample reply | Result |
| --- | --- |
| `Dzięki!` | Skipped: matches a thank-you phrase and is shorter than 10 words. |
| `Dzięki, ale ten link już nie działa i wymaga poprawy.` | Skipped by the thank-you rule, even though it reaches 10 words. |
| `Nowa lista działa poprawnie na wszystkich urządzeniach od dzisiejszego poranka.` | Passes the content filters: 10 words and no thank-you phrase. |
| `[quote]A much longer quoted response from another member.[/quote] dzięki` | The quote is excluded; the remaining acknowledgement is skipped. |
| A new topic with a short body | Not subject to reply-only content filters; the new-topic switch and access checks still apply. |

Click **Save settings** when the rules behave as intended. Other shoutbox settings and conversation history are retained.

## Existing history and switching filters off

If existing-card filtering is enabled, previously recorded activity cards are evaluated using the current rules when read. Turning the relevant filter off can make those recorded cards visible again. Turning existing-card filtering off leaves recorded cards outside the content filters, while new events still use the configured rules.

An event rejected when a post is created never gets a chat activity row. Disabling a filter later does **not** backfill that missing notification. In all cases the original forum post, thank-you/unlock behaviour and stored conversations remain intact.

## Updating an installation

Back up, deactivate the plugin, upload the complete contents of `Upload`, then activate again and refresh the forum. Do not uninstall for an update. New settings are added without resetting existing values. Open Activity bot afterwards to review the defaults and test examples from your community.
