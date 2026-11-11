type Activity = Record<'en' | 'sw', string>

const activities: Record<string, Record<string, Activity>> = {
  Mathematics: {
    Fractions: {
      en: 'Fold a sheet of paper into four equal parts. Ask your child to shade one part and name the fraction. Then ask them to shade two parts and explain why two quarters make one half.',
      sw: 'Kunja karatasi ili kupata sehemu nne zilizo sawa. Mwombe mtoto wako apake rangi sehemu moja na aitaje kama robo moja. Kisha apake rangi sehemu mbili na aeleze kwa nini robo mbili ni sawa na nusu.',
    },
    'Whole Numbers': {
      en: 'Write 24 and 42 on paper. Ask your child to circle the larger number and tell you how many tens and ones it has. Try again with 36 and 63.',
      sw: 'Andika 24 na 42 kwenye karatasi. Mwombe mtoto wako azungushie namba kubwa na aseme ina makumi mangapi na mamoja mangapi. Rudieni kwa kutumia 36 na 63.',
    },
    Addition: {
      en: 'Make two groups of bottle tops, one with five and one with three. Ask your child how many there are altogether. Check by joining the groups and counting them together.',
      sw: 'Tengeneza makundi mawili ya vifuniko vya chupa, moja lenye vitano na jingine lenye vitatu. Mwulize mtoto wako kuna vifuniko vingapi kwa jumla. Unganisheni makundi na mhesabu pamoja ili kukagua jibu.',
    },
    Subtraction: {
      en: 'Put eight beans on a plate and move three away. Ask how many are left and how your child worked it out. Repeat with six beans and remove two.',
      sw: 'Weka maharagwe manane kwenye sahani na uondoe matatu. Mwulize mtoto wako yamebaki mangapi na alipataje jibu. Rudieni kwa kutumia maharagwe sita na kuondoa mawili.',
    },
    Multiplication: {
      en: 'Set out three groups of four bottle tops. Ask your child how many there are and how the groups help them count. Then ask them to make two groups of five and check the total.',
      sw: 'Panga makundi matatu ya vifuniko vya chupa, kila kundi likiwa na vinne. Mwulize mtoto wako kuna vingapi na jinsi makundi yanavyomsaidia kuhesabu. Kisha mwombe atengeneze makundi mawili ya vitano na ahakikishe jumla.',
    },
    Division: {
      en: 'Ask your child to share twelve beans equally among three cups. Check that every cup has the same number. Ask what would happen if they used four cups.',
      sw: 'Mwombe mtoto wako agawanye maharagwe kumi na mawili kwa usawa katika vikombe vitatu. Hakikisheni kila kikombe kina idadi sawa. Mwulize nini kitatokea akitumia vikombe vinne.',
    },
    Lines: {
      en: 'Draw two lines that never meet, then draw two that cross. Ask your child to find one example of each around the home and explain how they can tell the difference.',
      sw: 'Chora mistari miwili sambamba, kisha chora mistari miwili inayokatana. Mwombe mtoto wako atafute mfano mmoja wa kila aina nyumbani na aeleze tofauti yake.',
    },
    Angles: {
      en: 'Use the corner of a sheet of paper as a right angle. Ask your child to find an angle at home that is the same size, then one that is larger. Have them use the paper corner to check.',
      sw: 'Tumia kona ya karatasi kama pembe mraba. Mwombe mtoto wako atafute pembe yenye ukubwa huo nyumbani, kisha atafute pembe kubwa zaidi. Atumie kona ya karatasi kukagua majibu.',
    },
    'Three Dimension (3-D) Objects': {
      en: 'Put a box and a tin on the table. Ask your child to predict which can roll and which can stack, then test both. Ask which surfaces explain what happened.',
      sw: 'Weka sanduku na kopo mezani. Mwombe mtoto wako atabiri kipi kinaweza kubingirika na kipi kinaweza kuwekwa juu ya kingine, kisha ajaribu vyote. Mwombe aeleze jinsi nyuso zake zinavyoathiri matokeo.',
    },
    'Decimals': {
      en: 'Write 0.4 and 0.35. Ask your child to rewrite 0.4 in hundredths, then show both numbers on a grid of 100 squares and circle the larger one.',
      sw: 'Andika 0.4 na 0.35. Mwombe mtoto wako aandike 0.4 kwa sehemu za mia, aonyeshe namba zote kwenye gridi ya visanduku 100, kisha azungushie iliyo kubwa.',
    },
    'Simple Equations': {
      en: 'Hide some of 12 beans under a cup and leave five visible. Ask your child to write an equation for the hidden number and lift the cup to check.',
      sw: 'Ficha baadhi ya maharagwe 12 chini ya kikombe na uache matano yaonekane. Mwombe mtoto wako aandike mlinganyo wa kupata idadi iliyofichwa, kisha ainue kikombe kukagua.',
    },
    'Length': {
      en: 'Ask your child to estimate the length of a table and then measure it with a tape measure. Compare the estimate with the measurement in centimetres.',
      sw: 'Mwombe mtoto wako akadirie urefu wa meza, kisha aupime kwa tepu ya kupimia. Linganisheni makadirio na kipimo kwa sentimita.',
    },
    'Area': {
      en: 'Draw two rectangles on squared paper, one 3 by 4 and one 2 by 6. Ask your child to count the squares and explain what the rectangles have in common.',
      sw: 'Chora mistatili miwili kwenye karatasi yenye visanduku, mmoja wa 3 kwa 4 na mwingine wa 2 kwa 6. Mwombe mtoto wako ahesabu visanduku na aeleze mistatili hiyo inafanana kwa njia gani.',
    },
    'Volume': {
      en: 'Build a solid block with two layers of six equal cubes. Ask your child to predict how many cubes it contains, including hidden ones, and take it apart to check.',
      sw: 'Jenga umbo lenye tabaka mbili za mchemraba sita kila tabaka. Mwombe mtoto wako atabiri jumla, akijumuisha mchemraba iliyofichika, kisha tenganisheni vipande ili kuhakiki.',
    },
    'Capacity': {
      en: 'Ask your child how many 250 mL cups of water will fill a 1 L jug. Use the same measuring cup to test their prediction.',
      sw: 'Mwulize mtoto wako vikombe vingapi vya mililita 250 vitajaza jagi la lita moja. Tumia kikombe hicho cha kupimia kujaribu jibu lake.',
    },
    'Mass': {
      en: 'Read the mass on two food packets. Ask your child which is heavier and how much heavier it is, converting to grams first if needed.',
      sw: 'Someni uzito ulioandikwa kwenye pakiti mbili za chakula. Mwulize mtoto wako ipi ni nzito zaidi na tofauti ni kiasi gani. Badilisheni vipimo kuwa gramu ikihitajika.',
    },
    'Time': {
      en: 'Ask your child when a 35-minute activity starting at 4:40 will end. Use a clock to count forward to 5:00 and then count the remaining minutes.',
      sw: 'Mwulize mtoto wako shughuli ya dakika 35 inayoanza saa 4:40 itaisha lini. Tumieni saa kuhesabu dakika hadi 5:00, kisha hesabu dakika zilizosalia.',
    },
    'Money': {
      en: 'Make a pretend shop with prices in shillings. Give your child KSh 100 in paper tokens and ask them to buy two items and work out the change.',
      sw: 'Tengenezeni duka la kuigiza lenye bei kwa shilingi. Mpe mtoto wako shilingi 100 za karatasi, kisha mwombe anunue vitu viwili na ahesabu chenji.',
    },
    'Data Representation': {
      en: 'Ask everyone at home to choose a favourite fruit. Have your child tally the answers, draw a bar chart and use it to find the most popular fruit.',
      sw: 'Mwombe kila mtu nyumbani ataje tunda analopenda zaidi. Mtoto wako aandike idadi ya kura, achore chati ya pau na aitumie kutambua tunda lililopata kura nyingi zaidi.',
    },
  },
  Agriculture: {
    'Soil Conservation': {
      en: 'Put the same amount of soil in two shallow trays. Cover the soil in one tray with dry leaves and leave the other bare. Tilt both trays the same way and slowly pour the same amount of water over each. Ask your child which tray loses more soil and how the leaves help keep it in place.',
      sw: 'Weka kiasi sawa cha udongo katika trei mbili. Funika udongo wa trei moja kwa majani makavu na uache mwingine wazi. Inamisha trei zote kwa kiwango sawa na umimine kiasi sawa cha maji taratibu juu ya kila moja. Mwombe mtoto wako aone maji kutoka trei gani yamebeba udongo mwingi zaidi, kisha aeleze jinsi majani yanavyosaidia kuzuia udongo kusombwa na maji.',
    },
    'Water Conservation': {
      en: 'Catch the water used to rinse vegetables in a bowl. Ask your child how much was collected and where it could be reused, such as on an outdoor plant.',
      sw: 'Kusanya maji yaliyotumika kuosha mboga kwenye bakuli. Mwulize mtoto wako mmekusanya kiasi gani na mnaweza kuyatumia tena wapi, kwa mfano kumwagilia mmea nje.',
    },
    'Conserving Wild Animals': {
      en: 'Draw a home for a wild animal your child knows. Ask them to label where it finds food, water and shelter and explain what would happen if one disappeared.',
      sw: 'Choreni makazi ya mnyama wa porini anayemjua mtoto wako. Mwombe aonyeshe mahali anapopata chakula, maji na maficho, kisha aeleze kitakachotokea kimojawapo kikikosekana.',
    },
    'Growing Vegetables': {
      en: 'Plant two bean seeds in a small container with drainage holes. Ask your child to record when the shoots appear and check the soil before each watering.',
      sw: 'Pandeni mbegu mbili za maharagwe kwenye chombo kidogo chenye mashimo ya kutoa maji. Mwombe mtoto wako aandike siku miche inapochipuka na akague udongo kabla ya kila umwagiliaji.',
    },
    'Uses of Domestic Animals': {
      en: 'Ask your child to draw a cow, a hen and a donkey. Beside each, write or draw one way it helps people and one thing people must do to care for it.',
      sw: 'Mwombe mtoto wako achore ng’ombe, kuku na punda. Kando ya kila mmoja aandike au achore njia moja ambayo mnyama huyo huwasaidia watu na jambo moja la kufanya ili kumtunza.',
    },
    'Preservation of Cereals and Pulses': {
      en: 'Show your child how you store dry beans or maize. Ask them to identify what keeps moisture and insects out, then compare that container with an open bag.',
      sw: 'Mwonyeshe mtoto wako jinsi mnavyohifadhi maharagwe makavu au mahindi. Mwombe ataje kinachozuia unyevu na wadudu kuingia, kisha alinganishe chombo hicho na mfuko ulio wazi.',
    },
    'Food Nutrients': {
      en: 'Use foods from one meal, such as ugali, beans and greens. Ask your child which foods mainly provide energy, which help growth and which add vitamins, then plan a similar meal with different foods.',
      sw: 'Tumia vyakula vya mlo mmoja, kama ugali, maharagwe na mboga za majani. Mwulize mtoto wako vipi hutupa nguvu, vipi husaidia ukuaji na vipi huongeza vitamini. Kisha pangeni mlo kama huo kwa vyakula tofauti.',
    },
    'Cooking Food': {
      en: 'Ask your child to write four steps for preparing a familiar meal. Have them put washing and preparation in the right order and identify which steps need an adult.',
      sw: 'Mwombe mtoto wako aandike hatua nne za kutayarisha mlo anaoufahamu. Apange hatua za kuosha na kuandaa kwa mpangilio sahihi na ataje zile zinazohitaji mtu mzima.',
    },
    'Good Grooming Practices': {
      en: 'Have your child make a simple morning checklist for brushing teeth, washing and putting on clean clothes. Ask them to explain why each step belongs on the list.',
      sw: 'Mwombe mtoto wako atengeneze orodha fupi ya mambo ya kufanya asubuhi, kama kupiga mswaki, kuoga na kuvaa nguo safi. Aeleze kwa nini kila hatua ni muhimu.',
    },
    'Home Hygiene': {
      en: 'Ask your child to choose one kitchen surface and identify when it needs cleaning. Have them demonstrate how to wipe it with a clean cloth and explain where the cloth goes afterwards.',
      sw: 'Mwombe mtoto wako achague sehemu moja jikoni na aseme inahitaji kusafishwa lini. Aonyeshe jinsi ya kuifuta kwa kitambaa safi na aeleze atakiweka wapi baada ya hapo.',
    },
    'Laundering Cotton Item': {
      en: 'Wash a cotton handkerchief together. Ask your child to explain why you rinse out the soap and hang the cloth open so it dries.',
      sw: 'Fueni leso ya pamba pamoja. Mwombe mtoto wako aeleze kwa nini mnasuuza sabuni na kutandaza leso ili ikauke.',
    },
    'Repairing Garments': {
      en: 'Give your child an old garment and ask them to identify a loose button or small tear. Have them mark where the repair should go, then practise a stitch on scrap cloth with an adult.',
      sw: 'Mpe mtoto wako nguo ya zamani atambue kifungo kilicholegea au sehemu ndogo iliyochanika. Aonyeshe pa kurekebisha, kisha ajaribu kushona kwenye kipande cha kitambaa akisaidiwa na mtu mzima.',
    },
    'Constructing Innovative Gardens': {
      en: 'Ask your child to sketch a vegetable garden in a reused container. Have them mark where drainage holes should go and choose a spot that gets sunlight.',
      sw: 'Mwombe mtoto wako achore bustani ya mboga kwenye chombo kilichotumika. Aonyeshe pa kuweka mashimo ya kutoa maji na achague mahali penye mwanga wa jua.',
    },
  },
  'Science and Technology': {
    'Classification of Plants': {
      en: 'Compare leaves from three familiar plants. Ask your child to group them by one visible feature, such as leaf shape, and explain where a fourth leaf belongs.',
      sw: 'Linganisheni majani ya mimea mitatu mnayoijua. Mwombe mtoto wako ayapange kwa sifa moja inayoonekana, kama umbo, na aeleze jani la nne linafaa kuwekwa katika kundi gani.',
    },
    'Invertebrates': {
      en: 'Look at pictures of a snail, butterfly and spider. Ask your child to sort them by number of legs and explain what they share despite those differences.',
      sw: 'Tazameni picha za konokono, kipepeo na buibui. Mwombe mtoto wako awapange kwa idadi ya miguu na aeleze jambo wanalofanana licha ya tofauti hizo.',
    },
    'The Human Breathing System': {
      en: 'Ask your child to trace the path of air from the nose to the lungs on a simple drawing. Then compare how fast they breathe sitting down and after walking around the room.',
      sw: 'Mwombe mtoto wako aonyeshe njia ya hewa kutoka puani hadi kwenye mapafu kwenye mchoro rahisi. Kisha linganisheni kasi ya kupumua akiwa ameketi na baada ya kutembea chumbani.',
    },
    'Mixtures': {
      en: 'Mix dry rice and beans in a bowl. Ask your child to choose a way to separate them and explain which difference between rice and beans makes their method work.',
      sw: 'Changanya mchele mkavu na maharagwe kwenye bakuli. Mwombe mtoto wako achague njia ya kuvitenganisha na aeleze ni tofauti gani inayofanya njia hiyo ifanye kazi.',
    },
    'Water Pollution': {
      en: 'Put clean water in two clear cups and add a little soil to one. Ask your child to compare them and suggest one way to stop soil or litter from washing into a stream.',
      sw: 'Weka maji safi katika vikombe viwili vinavyoonyesha kilichomo na ongeza udongo kidogo katika kimoja. Mwombe mtoto wako ayalinganishe na apendekeze njia moja ya kuzuia udongo au taka kusombwa hadi mtoni.',
    },
    'Floating and Sinking': {
      en: 'Ask your child to predict whether a coin and a bottle cap will float. Test them in a bowl of water, then turn the cap over and see whether the result changes.',
      sw: 'Mwombe mtoto wako atabiri kama sarafu na kifuniko cha chupa vitaelea. Vijaribuni kwenye bakuli la maji, kisha geuza kifuniko uone kama matokeo yatabadilika.',
    },
    'Sound Energy': {
      en: 'Stretch a rubber band around a small box and pluck it gently. Ask your child what they see moving and whether the sound changes when the band is stretched tighter.',
      sw: 'Vuta mpira wa kufungia kuzunguka kisanduku na uvute na kuuachia kwa kidole taratibu. Mwulize mtoto wako ni nini kinachotetemeka na kama sauti hubadilika mpira ukikazwa zaidi.',
    },
    'Heat Transfer': {
      en: 'Place a wooden spoon and a metal spoon in a cup of warm water. Ask your child to predict which handle will warm up first, then have them check.',
      sw: 'Weka kijiko cha mbao na cha chuma katika kikombe cha maji ya uvuguvugu. Mwombe mtoto wako atabiri mpini gani utapata joto kwanza, kisha akague.',
    },
  },
  'English': {
    'Listening Comprehension': {
      en: 'Read a short story aloud. Ask your child who it was about, what happened first, and which detail helped them answer.',
      sw: 'Soma hadithi fupi kwa sauti. Mwulize mtoto wako hadithi ilimhusu nani, nini kilitokea kwanza na ni jambo gani lililomsaidia kujibu.',
    },
    'Tense': {
      en: 'Write three English sentences about yesterday, today and tomorrow. Ask your child to underline each verb, then rewrite yesterday’s sentence to describe tomorrow.',
      sw: 'Andika sentensi tatu za Kiingereza kuhusu jana, leo na kesho. Mwombe mtoto wako apigie mstari kila kitenzi, kisha aandike upya sentensi ya jana ili ieleze kitakachotokea kesho.',
    },
    'Functional Writing': {
      en: 'Ask your child to write an invitation for a family activity. Give it to someone at home and see if they can tell what, when and where from the invitation alone.',
      sw: 'Mwombe mtoto wako aandike mwaliko wa shughuli ya familia. Mpe mtu wa nyumbani aone kama anaweza kujua shughuli yenyewe, wakati na mahali kwa kusoma mwaliko huo.',
    },
    'Word Classes': {
      en: 'Write ‘The small cat jumps.’ Ask your child to replace the describing word and the action word, then explain how the meaning changes.',
      sw: 'Andika ‘The small cat jumps.’ Mwombe mtoto wako abadilishe neno linaloeleza sifa na neno linaloonyesha kitendo, kisha aeleze maana inavyobadilika.',
    },
  },
  'Christian Religious Education': {
    'King Solomon the Wise Ruler': {
      en: 'Retell how Solomon asked for wisdom. Give your child a familiar disagreement, such as two siblings wanting the same book. Ask them to hear both sides and explain a fair solution.',
      sw: 'Simulia jinsi Sulemani alivyoomba hekima. Mpe mtoto wako mfano wa kutoelewana, kama watoto wawili kutaka kitabu kimoja. Mwombe asikilize pande zote na aeleze suluhisho la haki.',
    },
    'Family Unity': {
      en: 'Choose a family chore that is easier together. Ask your child to assign each person a small role and explain how helping one another makes the work easier.',
      sw: 'Chagueni kazi ya nyumbani inayokuwa rahisi mkifanya pamoja. Mwombe mtoto wako ampe kila mtu jukumu dogo na aeleze jinsi kusaidiana kunavyorahisisha kazi.',
    },
    'Parable of the Lost Sheep': {
      en: 'Read or retell the parable together. Ask your child why the shepherd went looking for one sheep and what they could do if someone felt left out at school.',
      sw: 'Someni au simulieni mfano wa kondoo aliyepotea. Mwulize mtoto wako kwa nini mchungaji alimtafuta kondoo mmoja na angefanya nini akiona mwenzake ametengwa shuleni.',
    },
  },
  'Kiswahili': {
    '10.2.1 Kusoma Kidijitali': {
      en: 'Open a short Kiswahili article on a shared phone. Ask your child to find its title, explain the main idea in their own words and point to a sentence that supports it.',
      sw: 'Fungueni makala fupi ya Kiswahili kwenye simu. Mwombe mtoto wako atambue kichwa chake, aeleze wazo kuu kwa maneno yake mwenyewe na aonyeshe sentensi inayoliunga mkono.',
    },
    '3.1.1 Vitendawili': {
      en: 'Take turns telling Kiswahili riddles. After each answer, ask your child which clue led to it, then help them write a riddle about something at home.',
      sw: 'Peaneni vitendawili kwa zamu. Baada ya kila jibu, mwulize mtoto wako ni kidokezo gani kilichomsaidia. Kisha msaidie kutunga kitendawili kuhusu kitu kilichopo nyumbani.',
    },
    '5.1.1 Methali': {
      en: 'Read ‘Haraka haraka haina baraka’ together. Ask your child to tell you about a time when rushing caused a mistake and explain how the proverb fits.',
      sw: 'Someni ‘Haraka haraka haina baraka’ pamoja. Mwombe mtoto wako asimulie wakati alipokosea kwa sababu ya kuharakisha na aeleze jinsi methali hiyo inavyofaa.',
    },
    '8.1.1 Visawe': {
      en: 'Write ‘furaha’ and ‘shangwe’. Ask your child to use each in a sentence and suggest another pair of words with similar meanings.',
      sw: 'Andika ‘furaha’ na ‘shangwe’. Mwombe mtoto wako atumie kila neno katika sentensi na ataje jozi nyingine ya maneno yenye maana zinazofanana.',
    },
  },
  'Creative Arts': {
    'Rhythm': {
      en: 'Clap a repeating four-beat pattern and ask your child to echo it. Have them change one beat, then see if you can echo their new pattern.',
      sw: 'Piga makofi kwa mpangilio wa midundo minne inayojirudia, kisha mwombe mtoto wako aigize mpangilio huo. Abadilishe mdundo mmoja nawe ujaribu kurudia mpangilio wake.',
    },
    'Painting and Mosaic': {
      en: 'Tear scrap paper into small pieces. Ask your child to arrange them into a picture and explain how they chose colours to show the edges and background.',
      sw: 'Rarueni karatasi iliyotumika iwe vipande vidogo. Mwombe mtoto wako avipange viunde picha, kisha aeleze jinsi alivyochagua rangi za kuonyesha kingo na mandharinyuma.',
    },
    'Puppetry': {
      en: 'Use a clean old sock as a puppet. Ask your child to tell a short story with it, changing its voice or movements to show how the character feels.',
      sw: 'Tumia soksi kuukuu iliyo safi kama kikaragosi. Mwombe mtoto wako asimulie hadithi fupi kwa kuitumia na abadilishe sauti au mienendo kuonyesha hisia za mhusika.',
    },
  },
  'Social Studies': {
    'Elements of a Map': {
      en: 'Draw a map from the front door to a room. Ask your child to add symbols and a key, then have someone follow the map to see if it works.',
      sw: 'Choreni ramani kutoka mlango wa mbele hadi kwenye chumba kimoja. Mwombe mtoto wako aongeze alama na ufafanuzi wake, kisha mtu mwingine aifuate ili kuona kama inaeleweka.',
    },
    'Weather and Climate': {
      en: 'Ask your child to record cloud cover or rain at the same time for five days. Discuss what the notes tell you about this week and what you would need to know about the usual weather over many years.',
      sw: 'Mwombe mtoto wako aandike hali ya mawingu au mvua kwa wakati uleule kwa siku tano. Jadilianeni rekodi hizo zinawaambia nini kuhusu wiki hii na mngehitaji habari gani ili kujua hali ya kawaida ya hewa kwa miaka mingi.',
    },
    'Democracy in Society': {
      en: 'Hold a secret family vote on which game to play. Ask your child to count the votes and suggest how to include someone whose choice lost.',
      sw: 'Fanyeni kura ya siri nyumbani kuchagua mchezo. Mwombe mtoto wako ahesabu kura na apendekeze njia ya kumshirikisha mtu ambaye chaguo lake halikushinda.',
    },
  },
  'Islamic Religious Education': {
    'Hadith on Gratitude': {
      en: 'Read the class Hadith on gratitude together. Ask your child to name one person who helped them this week and write or say a specific thank-you.',
      sw: 'Someni pamoja Hadithi kuhusu shukrani iliyosomwa darasani. Mwombe mtoto wako amtaje mtu aliyemsaidia wiki hii na aandike au amshukuru kwa kutaja alichofanya.',
    },
    'Rights of Neighbours': {
      en: 'Ask your child to suggest two ways to help a neighbour and choose one they can do with an adult. Discuss how they would ask first and respect the neighbour’s answer.',
      sw: 'Mwombe mtoto wako apendekeze njia mbili za kumsaidia jirani na achague moja anayoweza kufanya akiwa na mtu mzima. Jadilianeni jinsi ya kuuliza kwanza na kuheshimu jibu la jirani.',
    },
  },
  'Hindu Religious Education': {
    'Care for Creation': {
      en: 'Walk around your home with your child and choose one small way to care for a plant or reduce waste. Ask them to explain whom or what their action helps.',
      sw: 'Tembeeni karibu na nyumbani na mchague njia moja rahisi ya kutunza mmea au kupunguza taka. Mwombe mtoto wako aeleze tendo hilo linamsaidia nani au linafaidi nini.',
    },
    'Friendship and Keeping Promises': {
      en: 'Act out a scene where a friend cannot keep a promise. Ask your child what the friend could say, and what they could do to rebuild trust.',
      sw: 'Igizeni tukio ambalo rafiki hawezi kutimiza ahadi. Mwulize mtoto wako rafiki huyo anaweza kusema nini na anaweza kufanya nini ili aaminike tena.',
    },
  },
}

export function homeActivity(subject: string, topic: string, language: keyof Activity): string | undefined {
  return activities[subject]?.[topic.trim()]?.[language]
}
