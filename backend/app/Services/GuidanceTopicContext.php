<?php

namespace App\Services;

class GuidanceTopicContext
{
    // Topic notes ground generation. These are learning goals and constraints, not ready-made parent answers.
    // Sources: the project's existing teacher-facing curriculum and authored activity specifications.
    // They are not represented as official KICD wording or independent expert review.
    public static function for(string $subject, string $topic): ?array
    {
        $notes = [
            'Soil Conservation' => ['Ground cover reduces soil erosion by flowing water.', 'Compare equal soil in two shallow trays: dry leaves cover one, the other is bare; same tilt and small amount of water. Observe soil washed away. Adult supervises and wash hands.', 'udongo; mmomonyoko; majani makavu; maji kidogo; udongo uliofunikwa'],
            'Water Conservation' => ['Use water carefully and avoid wastage.', 'Compare the water used by a running tap and a small measured container without wasting water; collect water for reuse on plants.', 'kuhifadhi maji; kuzuia upotevu; chombo cha kupimia'],
            'Uses of Domestic Animals' => ['Recognise products and services from familiar domestic animals.', 'Use pictures or drawings, never live animals; connect a cow with milk, hen with eggs, donkey with transport and explain the link.', 'wanyama wa kufugwa; maziwa; mayai; usafiri'],
            'Food Nutrients' => ['Foods provide different nutrients; a balanced meal combines food groups.', 'Use a familiar meal or food pictures; sort foods supplying energy, body building and protective nutrients. No medical or diet prescriptions.', 'virutubisho; vyakula vya kujenga mwili; nguvu; kulinda mwili'],
            'Growing Vegetables' => ['Seeds need suitable conditions and care to grow.', 'Observe a planted seed in a reused container over several days, comparing changes and recording observations; no fertilisers or pesticides.', 'mbegu; maji; mwanga; ukuaji'],
            'Fractions' => ['A fraction represents equal parts of a whole; two quarters equal one half.', 'Use folded paper with equal parts, shading and comparison; ask why parts must be equal.', 'sehemu sawa; robo; nusu; karatasi'],
            'Whole Numbers' => ['Place value determines the value of digits and comparison of whole numbers.', 'Represent tens and ones with grouped bottle tops; compare two two-digit numbers and explain the tens before the ones.', 'makumi; mamoja; thamani ya tarakimu'],
            'Addition' => ['Addition combines quantities.', 'Combine two small groups of counters; predict the total, join and count to check.', 'kujumlisha; makundi; jumla'],
            'Subtraction' => ['Subtraction finds what remains after removing part of a quantity.', 'Start with counters, remove some, predict and count the remainder.', 'kutoa; vinavyobaki'],
            'Multiplication' => ['Multiplication represents equal groups.', 'Arrange counters into equal groups, predict the total and check using repeated addition.', 'kuzidisha; makundi sawa; jumla'],
            'Division' => ['Division can mean sharing equally.', 'Share a fixed number of counters among cups; compare the amount in each cup and check the total.', 'kugawanya; kwa usawa; vikombe'],
            'Lines' => ['Parallel straight lines stay the same distance apart and do not meet; intersecting lines cross.', 'Draw parallel and crossing straight lines on paper; identify matching edges at home and explain the difference.', 'mistari sambamba; mistari inayokatana; umbali sawa'],
            'Angles' => ['A right angle can be compared with larger and smaller angles.', 'Use a paper corner as a right-angle reference and compare book openings and corners.', 'pembe mraba; pembe ndogo; pembe kubwa'],
            'Three Dimension (3-D) Objects' => ['Solid shapes have flat or curved surfaces affecting rolling and stacking.', 'Compare a box and a sealed empty container; predict rolling and stacking, then observe surfaces.', 'maumbo; nyuso tambarare; nyuso zilizopinda'],
            'Decimals' => ['Decimal place value helps compare numbers; 0.4 equals 0.40.', 'Compare tenths and hundredths using squared paper; align place values before comparing.', 'desimali; sehemu za kumi; sehemu za mia'],
            'Simple Equations' => ['An unknown quantity can be found by balancing a simple equation.', 'Hide some counters under a cup while the total and visible quantity are known; write an equation and reveal to check.', 'mlinganyo; idadi isiyojulikana'],
            'Length' => ['Length measurements need consistent units.', 'Estimate a table length and measure using a ruler or tape; compare centimetres with the estimate.', 'urefu; sentimita; kukadiria'],
            'Area' => ['Area measures the surface covered in square units.', 'Compare rectangles on squared paper by counting squares, including differently shaped rectangles with equal area.', 'eneo; visanduku; vipimo vya mraba'],
            'Volume' => ['Volume of a solid can be counted in equal unit cubes.', 'Build a block in layers, predict hidden cubes and dismantle to check; do not confuse solid volume with liquid capacity.', 'ujazo; mchemraba; tabaka'],
            'Capacity' => ['Capacity is how much a container holds; one litre equals one thousand millilitres.', 'Use small measured cups and a jug in a shallow tray; predict the number of cups and check, with adult supervision.', 'ujazo wa chombo; lita; mililita'],
            'Mass' => ['Mass can be estimated and compared using a balance or kitchen scale.', 'Estimate and weigh two household items; check units and compare results.', 'uzito; mizani; gramu; kilogramu'],
            'Time' => ['Elapsed time compares a start and finish time.', 'Read a clock before and after a short household activity and count the elapsed minutes.', 'saa; dakika; muda uliopita'],
            'Money' => ['A purchase total and change can be checked by addition and subtraction.', 'Use pretend coins and labelled household items; calculate a small purchase and change without spending money.', 'pesa; bei; jumla; chenji'],
            'Data Handling' => ['Data can be counted, organised and compared.', 'Count household objects by category, record a tally and draw a small bar chart.', 'data; kuhesabu; jedwali; grafu'],
            'Mixtures' => ['Mixtures contain materials that can sometimes be physically separated.', 'Mix dry rice and beans and separate by hand according to visible differences; do not eat the materials afterwards.', 'mchanganyiko; kutenganisha; maharagwe; mchele'],
            'Water Pollution' => ['Visible soil or litter can contaminate water; clear water is not necessarily safe to drink.', 'Compare clear water and soil-water in cups; discuss preventing runoff. Never drink experimental water.', 'uchafuzi wa maji; udongo; taka; usinywe'],
            'Floating and Sinking' => ['Some objects float and others sink; shape can affect floating.', 'Predict a coin and bottle-cap result in a shallow bowl of water; observe and explain. Adult supervises.', 'kuelea; kuzama; umbo'],
            'Sound Energy' => ['Vibrations produce sound.', 'Gently pluck a rubber band stretched around a box, observe vibration and compare tension; do not aim bands at faces.', 'sauti; mtetemo; mpira wa kufungia'],
            'Heat Transfer' => ['Different materials transfer heat at different rates.', 'Adult sets out metal and wooden spoons in lukewarm water only; compare handles carefully; no hot water or heating appliances.', 'joto; uhamishaji; maji ya uvuguvugu; mbao; chuma'],
            'Invertebrates' => ['Invertebrates do not have a backbone and may differ in body parts.', 'Compare drawings or pictures of snail, butterfly and spider; identify similarities and count legs. No handling live animals.', 'wanyama wasio na uti wa mgongo; miguu'],
            'The Human Breathing System' => ['Air travels through the nose and airways to the lungs.', 'Trace the route on a simple drawing and notice breathing at rest versus gentle walking. No strenuous exercise or medical advice.', 'kupumua; hewa; pua; mapafu'],
            'Listening Comprehension' => ['Understanding a spoken story involves its events and supporting details.', 'Adult reads a short story; child retells who, what happened first and evidence for an answer.', 'kusikiliza; hadithi; maelezo'],
            'Reading Comprehension' => ['Understanding text includes its main idea and evidence.', 'Read a short passage together; child states the main idea and points to words that support it.', 'kusoma; wazo kuu; ushahidi'],
            'Punctuation' => ['Punctuation helps show sentence boundaries and meaning.', 'Compare a short unpunctuated sentence with a punctuated version; child adds capitals and full stops and reads aloud.', 'alama za uakifishaji; herufi kubwa; nukta'],
            'Tense' => ['Verb tense connects an action to its time.', 'Use simple English sentences about yesterday, today and tomorrow; identify and change the verb.', 'nyakati; kitenzi; jana; leo; kesho'],
            'Functional Writing' => ['An invitation communicates an event, time and place clearly.', 'Write a family-event invitation and ask another person to identify what, when and where from the text alone.', 'mwaliko; tukio; wakati; mahali'],
            'Word Classes' => ['Words have different grammatical jobs such as naming, describing and showing actions.', 'Change describing and action words in a short English sentence and explain the changed meaning.', 'aina za maneno; sifa; kitendo'],
            'King Solomon the Wise Ruler' => ['In the class Bible story, Solomon asks for wisdom; fair decisions involve considering others.', 'Retell the wisdom story using the school material; discuss a familiar disagreement, hear both sides and explain a fair choice. Do not invent scripture quotes.', 'Sulemani; hekima; uamuzi wa haki; kusikiliza pande zote'],
            'Family Unity' => ['Cooperation and shared responsibilities help family unity.', 'Plan small roles in a family chore and explain how helping each other supports the group.', 'umoja wa familia; kusaidiana; majukumu'],
            'Parable of the Lost Sheep' => ['The class parable illustrates care for someone who is missing or left out.', 'Read or retell using the school material; discuss how to include a classmate. No invented quotations.', 'kondoo aliyepotea; kujali; kumshirikisha mwenzako'],
            '10.2.1 Kusoma Kidijitali' => ['Digital reading involves recognising a title, main idea and supporting detail.', 'Use a short age-appropriate Kiswahili text on a shared device; identify title, main idea and a supporting sentence.', 'kusoma kidijitali; kichwa; wazo kuu; sentensi'],
            '3.1.1 Vitendawili' => ['Riddles use clues to point to an answer.', 'Exchange a familiar Kiswahili riddle; explain the clue and compose a new one about an ordinary object.', 'kitendawili; kidokezo; jibu'],
            '5.1.1 Methali' => ['Proverbs express lessons that can apply to everyday situations.', 'Discuss Haraka haraka haina baraka and a familiar example of a mistake caused by rushing; explain the connection.', 'methali; kuharakisha; kosa; funzo'],
            '8.1.1 Visawe' => ['Synonyms have similar meanings, though context can matter.', 'Use furaha and shangwe in sentences; suggest and discuss another pair of Kiswahili synonyms.', 'visawe; maana zinazofanana; sentensi'],
            'Rhythm' => ['Rhythm is a pattern of beats and rests.', 'Clap a short repeating beat, echo it, change one beat and compare the patterns.', 'mdundo; makofi; mpangilio unaojirudia'],
            'Painting and Mosaic' => ['A mosaic forms an image from arranged small pieces.', 'Tear scrap paper by hand, arrange colours into a picture and discuss edges and background; no sharp tools.', 'vipande vya karatasi; rangi; picha; kingo'],
            'Puppetry' => ['A puppet can communicate character and feelings through voice and movement.', 'Use a clean old sock for a short story and explain changes of voice or movement.', 'kikaragosi; mhusika; hisia; sauti'],
            'Elements of a Map' => ['A simple map uses symbols and a key to communicate locations.', 'Draw an indoor route with symbols and a key; another person follows it to check clarity.', 'ramani; alama; ufafanuzi; njia'],
            'Weather and Climate' => ['Weather describes short-term conditions; climate describes typical patterns over many years.', 'Record daily weather briefly and explain why these few observations do not establish climate.', 'hali ya hewa; tabianchi; miaka mingi'],
            'Democracy in Society' => ['Voting involves making choices fairly and respecting others.', 'Hold a small family vote and count choices; discuss including someone whose choice lost.', 'demokrasia; kura; kuheshimu; kuwashirikisha'],
            'Hadith on Gratitude' => ['Gratitude recognises help received from others.', 'Use the class Hadith text rather than inventing a quote; name a helpful action and express specific thanks.', 'shukrani; msaada; kumshukuru'],
            'Rights of Neighbours' => ['Neighbours deserve respect, consideration and consent.', 'Discuss a helpful action with an adult and how to ask permission and respect a refusal.', 'jirani; haki; heshima; ruhusa'],
            'Care for Creation' => ['Care for living things and reduced waste are practical responsibilities.', 'Choose a small supervised plant-care or waste-reduction action and explain its benefit.', 'kutunza; mmea; kupunguza taka'],
            'Friendship and Keeping Promises' => ['Honesty and communication help trust when a promise cannot be kept.', 'Role-play a familiar promise, explain the difficulty honestly and suggest a respectful way to rebuild trust.', 'urafiki; ahadi; uaminifu'],
        ];
        if (! isset($notes[trim($topic)])) return null;
        [$goal, $constraints, $vocabulary] = $notes[trim($topic)];
        return ['learning_goal' => $goal, 'activity_constraints' => $constraints, 'kiswahili_vocabulary' => $vocabulary];
    }

    public static function conceptChecks(string $topic): array
    {
        return match (trim($topic)) {
            'Soil Conservation' => [['soil', 'udongo'], ['water', 'maji'], ['leaves', 'cover', 'majani', 'funik']],
            'Lines' => [['parallel', 'sambamba'], ['cross', 'intersect', 'katana']],
            'Fractions' => [['equal', 'sawa'], ['paper', 'karatasi']],
            default => [],
        };
    }
}
